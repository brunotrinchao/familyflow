<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionSourceEnum;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Services\CategoryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class TransactionTransferFormModal
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('source_custom')
                            ->label('Saiu da conta')
                            ->placeholder('Selecione')
                            ->options(function () {
                                return Account::get()
                                    ->mapWithKeys(fn ($account) => [
                                        "account__{$account->id}" => "{$account->name}",
                                    ])
                                    ->toArray();
                            })
                            ->required(),
                        Select::make('account_destine')
                            ->label('Entrou na conta')
                            ->placeholder('Selecione')
                            ->options(function () {
                                return Account::get()
                                    ->mapWithKeys(fn ($account) => [
                                        "account__{$account->id}" => "{$account->name}",
                                    ])
                                    ->toArray();
                            })
                            ->required(),
                    ]),
                Hidden::make('title')
                    ->default('Transfência entre contas'),
                TextInput::make('description')
                    ->label('Descrição')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Valor') // É uma boa prática avisar, já que o BD armazena em int
                            ->mask(MaskHelper::maskMoney())
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->minValue(0)
                            ->prefix('R$')
                            ->extraAttributes(['class' => 'text-center']),

                        DatePicker::make('date')
                            ->label('Data da Transação')
                            ->required()
                            ->default(now())
                    ]),
                Section::make('')
                    ->schema([
                        Toggle::make('is_recurring')
                            ->label('Repetir?')
                            ->reactive()
                            ->onIcon('heroicon-m-calendar-days')
                            ->offIcon('heroicon-m-calendar-days')
                            ->default(false),
                        TextInput::make('installment_number')
                            ->label('Número de Parcelas')
                            ->numeric()
                            ->minValue(2)
                            ->default(1)
                            ->required(fn (Get $get): bool => $get('is_recurring'))
                            ->hidden(fn (Get $get): bool => !$get('is_recurring'))
                            ->reactive()
                            ->helperText(function ($get): HtmlString {
                                $installments = (int)$get('installment_number');

                                $amount = MaskHelper::covertStrToInt($get('amount'));

                                $html = '';
                                if ($amount && $installments > 0) {
                                    $value = round($amount / $installments, 2); // preserva 2 casas decimais corretamente
                                    $html = '<b>Serão lançadas ' . $installments . ' parcelas de R$ ' . MaskHelper::covertIntToReal($value) . '</b><br/>';

                                    $html .= '<small>Em caso de divisão não exata, a sobra será somada à primeira parcela.</small>';
                                }
                                return new HtmlString($html);
                            })
                    ])
            ]);
    }
}
