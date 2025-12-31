<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryColorPaletteEnum;
use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use Awcodes\Palette\Forms\Components\ColorPicker;
use Awcodes\Palette\Forms\Components\ColorPickerSelect;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Guava\IconPicker\Forms\Components\IconPicker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;

class CategoryForm
{
    public static function configure(Schema $schema, ?TransactionTypeEnum $type = null): Schema
    {
        $isTypeSet = $type !== null;

        return $schema
            ->components([
                Grid::make()
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da Categoria')
                            ->required()
                            ->maxLength(255)
                            ->unique(table          : 'categories', // Specify the database table name
                                     column         : 'name',
                                     modifyRuleUsing: function (Unique $rule, Get $get) {
                                    return $rule->where('family_id', Filament::getTenant()->id)
                                        ->where('type', $get('type'));
                                }),
                        //                        ColorPicker::make('color')
                        //                            ->required(),
                        Select::make('type')
                            ->label('Tipo de Categoria')
                            ->options(CategoryTypeEnum::class)
                            ->required()
                            ->default($type)
                            ->disabled($isTypeSet),

                    ])
                    ->columnSpanFull(),
                Section::make('Escolha um ícone')
                    ->schema([
                        RadioDeck::make('icon')
                            ->label('Selecione')
                            ->options(CategoryIconEnum::class)
                            //                            ->descriptions(CategoryIconEnum::class)
                            ->icons(CategoryIconEnum::class)
                            ->required()
                            ->columns(3)
                            ->extraAttributes([
                                'class' => 'list-icon-category'
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
                Section::make('Escolha uma cor')
                    ->schema([
                        ColorPicker::make('color')
                            ->label('Selecione')
                            ->required()
                            ->colors(CategoryColorPaletteEnum::getColorsList())
                    ])
                    ->collapsible()
                    ->collapsed(),


            ]);
    }
}
