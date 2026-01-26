<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use App\Services\CategoryService;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class InvoiceFormModal
{
    public static int $amountTotal = 0;

    public static function configure(Schema $schema, ?TransactionTypeEnum $type = null): Schema
    {

        return $schema
            ->components([
                TextInput::make('description')
                    ->label('Descrição')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull()
                    ->disabled(),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount_total')
                            ->default(fn ($get) => $get('amount'))
                            ->formatStateUsing(fn ($get) => $get('amount'))
                            ->mask(MaskHelper::maskMoney())
                            ->hidden()
                            ->readonly(),
                        TextInput::make('amount')
                            ->label('Valor')
                            ->mask(MaskHelper::maskMoney())
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->minValue(0)
                            ->prefix('R$')
                            ->extraAttributes(['class' => 'text-center']),

                        DatePicker::make('date')
                            ->label('Data')
                            ->required()
                            ->default(now())
                    ]),
                Grid::make(2)
                    ->schema([
                        Select::make('source_custom')
                            ->label('Conta')
                            ->placeholder('Selecione')
                            ->options(function () {
                                $accounts = Account::get()
                                    ->mapWithKeys(fn ($account) => [
                                        "account__{$account->id}" => "{$account->name}",
                                    ])
                                    ->toArray();
                                return $accounts;
                            })
                            ->reactive()
                            ->required()
                            ->disabled(function (string $operation) {
                                return $operation == 'view.edit-transaction-modal';
                            }),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->options(fn () => app(CategoryService::class)->getOptionsForType($type))
                            ->placeholder('Selecione')
                            ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                            ->required()
                            ->preload()
                    ]),
                Grid::make(1)
                    ->disabled()
                    ->schema([
                        Section::make()
                            ->visible(function ($get, $set, Model $record): bool {


                                $amount = MaskHelper::covertStrToInt($get('amount'));
                                $amountTotal = $record->total_amount * -1;

                                $calcTotal = $amountTotal - $amount;

                                return $calcTotal > 0 || $calcTotal < 0;
                            })
                            ->schema(
                                [
                                    Placeholder::make('No Label')
                                        ->hiddenLabel()
                                        ->reactive()
                                        ->content(function ($get, $set, Model $record): HtmlString {


                                            $amount = MaskHelper::covertStrToInt($get('amount'));
                                            $amountTotal = $record->total_amount * -1;

                                            $calcTotal = $amountTotal - $amount;

                                            $balance = MaskHelper::covertIntToReal($calcTotal);
                                            $amountFormated = MaskHelper::covertIntToReal($amount);

                                            $date = Carbon::parse($record->period_date);
                                            $nextMonth = Carbon::parse($record->period_date)
            ->addMonth()
            ->translatedFormat('F \d\e Y');

//                                            $html = $calcTotal > 0 ? "<small>O valor restante (<b>{$balance}</b>) será lançado para a próxima fatura (<b>{$currentMonth->translatedFormat('F \d\e Y')}</b>).</small>" : "<small>Valor da fatura (<b>{$amountFormated}</b>) menor que o informado.</small>";

                                            if ($calcTotal > 0) {
            $html = "<div class='text-sm text-primary-600'>O valor restante (<b>{$balance}</b>) será lançado para a próxima fatura (<b>{$nextMonth}</b>).</div>";
        } elseif ($calcTotal < 0) {
            $html = "<div class='text-sm text-danger-600'>O valor informado excede a fatura em <b>{$balance}</b>. Não será possivel pagar a fatura.</div>";
        } else {
            $html = "<div class='text-sm text-success-600'>O valor informado cobre exatamente o total da fatura.</div>";
        }

                                            return new HtmlString($html);
                                        })
                                ]
                            )
                            ->compact(),
                        Toggle::make('status')
                            ->label('Pago?')
                            ->inline(false)
                            ->onIcon(Iconoir::DollarCircleSolid)
                            ->offIcon(Iconoir::DollarCircle)
                            ->onColor('success')
                            ->reactive()
                            ->visible(fn ($record) => $record == null)
                            ->dehydrated()
                            ->columnSpanFull()

                    ]),
            ]);
    }
}
