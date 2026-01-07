<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Enums\CategoryColorPaletteEnum;
use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Models\Category;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class CategoryTable
{
    public static function configure(): array
    {
        return [
            IconColumn::make('ico')
                ->label('')
                    ->color(function (mixed $record) {
                        return Color::hex($record->color->value);
                    })
                    ->getStateUsing(function (mixed $record) {
                        return $record->icon;
                    })
                    ->extraAttributes(function (mixed $record): array {
                        $colorSource = $record->color->value;

                        $colorHex = Color::generatePalette($colorSource)[200];

                        return [
                            'style' => "background-color: {$colorHex}; display: inline-flex;
                                        align-items: center;
                                        justify-content: center;
                                        padding: 6px 9px !important;
                                        border-radius: 50%; /* Torna-o circular */
                                        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Sombra suave */
                                        flex-shrink: 0;
                                        color: #ffffff;
                                        width: 40px;
                                        height: 40px;"
                        ];
                    }),
            TextColumn::make('name')
                ->label(''),
            TextColumn::make('type')
                ->label('')
                ->icon(false)
                ->badge(),
            TextColumn::make('default_status')
                ->label('')
                ->badge()
                ->size(TextSize::Small)
                ->color('#ffffff')
                ->getStateUsing(fn (Category $record): string => $record->family_id === null ? 'Padrão' : ''
                )
        ];
    }
}
