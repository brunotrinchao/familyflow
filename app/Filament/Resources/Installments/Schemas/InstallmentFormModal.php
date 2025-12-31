<?php

namespace App\Filament\Resources\Installments\Schemas;

use App\Enums\CategoryTypeEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use Carbon\Carbon;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;


class InstallmentFormModal
{

    public static function configure(Schema $schema, ?TransactionTypeEnum $type = null): Schema
    {

        return $schema
            ->components([
                Radio::make('type')
                    ->hiddenLabel()
                    ->reactive()
                    ->inline()
                    ->options([
                        TransactionTypeEnum::EXPENSE->value => TransactionTypeEnum::EXPENSE->getLabel(),
                        TransactionTypeEnum::INCOME->value  => TransactionTypeEnum::INCOME->getLabel()
                    ]),
                TextInput::make('transaction.title')
                    ->label('Descrição')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull()
                    ->disabled(),
                Grid::make(2)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Valor')
                            ->mask(MaskHelper::maskMoney())
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->minValue(0)
                            ->prefix('R$')
                            ->extraAttributes(['class' => 'text-center']),

                        DatePicker::make('transaction.date')
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
                                        "account__{$account->id}" => "{$account->name}",
                                    ])
                                    ->toArray();

                                $cards = CreditCard::get()
                                    ->mapWithKeys(fn ($card) => [
                                        "credit_card__{$card->id}" => "{$card->name} ({$card->last_four_digits})",
                                    ])
                                    ->toArray();
                                return [
                                    'Contas Bancárias'   => $accounts,
                                    'Cartões de Crédito' => $cards,
                                ];
                            })
                            ->reactive()
                            ->required(),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->reactive()
                            ->options(fn ($get) => Category::where('type', $get('type'))->pluck('name', 'id')->toArray())
                            ->createOptionForm(fn (Schema $schema,
                                                          $get) => CategoryForm::configure($schema, $get('type')))
                            ->createOptionUsing(function (array $data, $get): int {
                                $data['type'] = CategoryTypeEnum::from($get('type')->value);
                                $category = Category::create($data);
                                return $category->id;
                            })
                            ->placeholder('Selecione')
                            ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                            ->required()
                            //                            ->searchable()
                            ->preload(),
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

                                    $options[$key] = Str::ucfirst($label);
                                }

                                // 2. Adiciona o Mês Atual
                                $keyCurrent = $currentMonth->format('Y-m');
                                $labelCurrent = $currentMonth->translatedFormat('F \d\e Y') . ' (Atual)';
                                $options[$keyCurrent] = Str::ucfirst($labelCurrent);

                                // 3. Loop para os 2 meses futuros
                                for ($i = 1; $i <= 2; $i++) {
                                    $month = $currentMonth->copy()->addMonths($i);
                                    $key = $month->format('Y-m');
                                    $label = $month->translatedFormat('F \d\e Y');

                                    $options[$key] = Str::ucfirst($label);
                                }

                                return $options;
                            })
                            ->columnSpan(2),
                        // 🚨 NOVO CAMPO DE STATUS ADICIONADO AQUI
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options(InstallmentStatusEnum::class)
                            ->required()
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
                Textarea::make('transaction.description')
                    ->label('Observação')
                    ->required()
                    ->columnSpanFull()
                    ->disabled(),
                Section::make('')
                    ->visible(fn ($record): bool => $record->transaction->installment_number > 1)
                    ->description('Este lançamento se repete em outras datas.')
                    ->extraAttributes(['class' => 'bg-blue-100 dark:bg-blue-900'])
                    ->hiddenLabel()
                    ->schema([
                        Radio::make('update_mode') // 🚨 Adicionado o nome do campo aqui
                        ->hiddenLabel() // Oculta o label interno para não repetir a info da Section
                        ->options([
                            'update_only_this' => 'Atualizar apenas este lançamento',
                            'update_future'    => 'Atualizar este e os próximos',
                            'update_all'       => 'Atualizar todos os lançamentos',
                        ])
                            ->reactive()
                            ->default('update_only_this') // Boa prática definir um padrão
                            ->required()
                            ->afterStateUpdated(function ($state) {
                                Notifications::alignment(Alignment::Center);
                                Notifications::verticalAlignment(VerticalAlignment::Center);
                                if ($state === 'update_all') {
                                    Notification::make('alert')
                                        ->title('Você tem certeza?!')
                                        ->body('Essa operação não poderá ser desfeita.

Você irá atualizar todos os lançamentos, inclusive os de datas passadas.

Preste atenção principalmente se você estiver atualizando o valor.')
                                        ->warning()    // Ou ->danger() para dar mais destaque
                                        //
                                        ->send();
                                }
                            }),
                    ])
                    ->compact()
            ]);
    }
}
