<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TransactionFormModal
{

    public static function configure(Schema $schema, ?TransactionTypeEnum $type = null): Schema
    {

        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Descrição')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Valor')
                            ->mask(MaskHelper::maskMoney())
                            ->default(0)
                            ->required()
                            ->reactive()
//                            ->rules(['numeric', 'min:0'])
                            ->minValue(0)
                            ->prefix('R$')
                            ->extraAttributes([
                                'class'      => 'text-center',
                                'step'       => '0.01',
                                // Garante incremento de centavos
                                'onkeypress' => 'return event.charCode >= 48'
                                // Impede o sinal de "-" no teclado
                            ]),

                        DatePicker::make('date')
                            ->label('Data')
                            ->required()
                            ->default(now())
                    ]),
                Grid::make(2)
                    ->schema([
                        Select::make('source_custom')
                            ->label('Conta/Cartão')
                            ->placeholder('Selecione')
                            ->options(function () {
                                $accounts = Account::get()
                                    ->mapWithKeys(fn ($account) => [
                                        "account__{$account->id}" => "Conta: {$account->name}",
                                    ])
                                    ->toArray();

                                $cards = CreditCard::get()
                                    ->mapWithKeys(fn ($card) => [
                                        "credit_card__{$card->id}" => "Cartão: {$card->name} ({$card->last_four_digits})",
                                    ])
                                    ->toArray();
                                return [
                                    'Contas Bancárias'   => $accounts,
                                    'Cartões de Crédito' => $cards,
                                ];
                            })
                            ->reactive()
                            ->required()
                            ->disabled(function (string $operation) {
                                return $operation == 'view.edit-transaction-modal';
                            })
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->options(fn () => Category::where('type', $type)->pluck('name', 'id')->toArray())
                            ->createOptionForm(fn (Schema $schema) => CategoryForm::configure($schema, $type))
                            ->createOptionUsing(function (array $data) use ($type): int {
                                $data['type'] = CategoryTypeEnum::from($type->value);
                                $category = Category::create($data);
                                return $category->id;
                            })
                            ->placeholder('Selecione')
                            ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                            ->required()
                            //                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Observações')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Grid::make(3)
                    ->schema([
                        Select::make('invoice')
                            ->label('Fatura de')
                            ->visible(fn (Get $get): bool => // Verifica o campo de seleção unificada (ex: 'card_12' ou 'account_5')
                            Str::startsWith($get('source_custom'), 'credit_card__')
                            )
                            ->required(fn (Get $get): bool => // Verifica o campo de seleção unificada (ex: 'card_12' ou 'account_5')
                            Str::startsWith($get('source_custom'), 'credit_card__')
                            )
                            ->default(Carbon::now()->format('Y-m')) // Define o mês atual como padrão
                            ->preload()
                            ->reactive()
                            ->native(false)
                            ->options(function () {
                                $currentMonth = Carbon::now()->startOfMonth();
                                $options = [];

                                // 1. Loop para os 2 meses anteriores
                                for ($i = 2; $i >= 1; $i--) {
                                    $month = $currentMonth->copy()->subMonths($i);
                                    // Formato da Chave: YYYY-MM (Ideal para ordenação e backend)
                                    $key = $month->format('Y-m');
                                    // Formato do Rótulo: Ex: Outubro/2025
                                    $label = $month->translatedFormat('F \d\e Y');

                                    $options[$key] = $label;
                                }

                                // 2. Adiciona o Mês Atual
                                $keyCurrent = $currentMonth->format('Y-m');
                                $labelCurrent = $currentMonth->translatedFormat('F \d\e Y') . ' (Atual)';
                                $options[$keyCurrent] = $labelCurrent;

                                // 3. Loop para os 2 meses futuros
                                for ($i = 1; $i <= 2; $i++) {
                                    $month = $currentMonth->copy()->addMonths($i);
                                    $key = $month->format('Y-m');
                                    $label = $month->translatedFormat('F \d\e Y');

                                    $options[$key] = $label;
                                }

                                return $options;
                            })
                            ->columnSpan(2)
                    ]),
                Section::make()
                    ->visible(fn (Get $get): bool => // Verifica o campo de seleção unificada (ex: 'card_12' ou 'account_5')
                        MaskHelper::covertStrToInt($get('amount')) > 0
                    )
                    ->reactive()
                    ->afterHeader([
                        Toggle::make('is_recurring')
                            ->label('Parcelada?')
                            ->reactive()
                            ->onIcon('heroicon-m-calendar-days')
                            ->offIcon('heroicon-m-calendar-days')
                            ->default(false),
                    ])
                    ->schema([
                        TextInput::make('installment_number')
                            ->label('Número de Parcelas')
                            ->numeric()
                            ->minValue(2)
                            ->default(2)
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

                                    $html .= '<small>Em caso de divisão não exata, a sobra será somada à última parcela.</small>';
                                }
                                return new HtmlString($html);
                            })
                    ]),
                Grid::make(1)
                    ->schema([
                        Toggle::make('status')
                            ->label('Pago?')
                            ->inline(false)
                            ->helperText('Lançamento ja foi pago?')
                            ->onIcon(Iconoir::DollarCircleSolid)
                            ->offIcon(Iconoir::DollarCircle)
                            ->onColor('success')
                    ]),
            ]);
    }
}
