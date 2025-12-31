<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionSourceEnum;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Helpers\MaskHelper;
use App\Models\Category;
use App\Services\CategoryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Illuminate\Support\Collection;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label('Valor') // É uma boa prática avisar, já que o BD armazena em int
                    ->mask(MaskHelper::maskMoney())
                    ->default(0)
                    ->required()
                    ->minValue(0)
//                    ->extraInputAttributes(['style' => 'font-size:2.25em;padding:0.75em;font-weight:bold;'])
                    ->columnSpanFull(),

                DatePicker::make('date')
                    ->label('Data da Transação')
                    ->required()
                    ->default(now()),
                Select::make('category_id')
                    ->label('Categoria')
                    ->options(fn () => Category::get()->groupBy('type')->map->pluck('name', 'id'))
                    ->createOptionForm(fn (Schema $schema) => CategoryForm::configure($schema))
                    ->createOptionUsing(function (array $data): int {
                        $category = Category::create($data);
                        return $category->id;
                    })
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name)
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('source')
                    ->label('Origem')
                    ->options(TransactionSourceEnum::class)
                    ->required(),
                Select::make('type')
                    ->label('Tipo')
                    ->options(TransactionTypeEnum::class)
                    ->required(),
                Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3)
                    ->maxLength(255),
                // NOVO CAMPO DE TOGGLE: Perguntando se é parcelado
                Toggle::make('is_recurring')
                    ->label('Transação Recorrente / Parcelada?')
                    ->reactive() // Torna o componente reativo para poder ser usado na condição abaixo
                    ->onIcon('heroicon-m-calendar-days')
                    ->offIcon('heroicon-m-calendar-days'),

                // NOVO CAMPO DE PARCELAS: Aparece apenas se 'is_recurring' for verdadeiro
                TextInput::make('installment_number')
                    ->label('Número de Parcelas')
                    ->numeric()
                    ->minValue(2) // Parcelamento geralmente começa com 2 ou mais
                    ->default(1)
                    ->required(fn (Get $get): bool => $get('is_recurring')) // Torna obrigatório se for recorrente
                    ->hidden(fn (Get $get): bool => ! $get('is_recurring')) // ESCONDE se o toggle for falso
                    ->helperText('O número total de parcelas a serem geradas.'),

            ]);
    }
}
