<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Enums\TransactionSourceEnum;
use App\Helpers\MaskHelper;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class AccountTable
{
    public static function configure(): array
    {
        return [
            TextColumn::make('brand.icon_path')
                ->label('')
                ->getStateUsing(function (mixed $record): HtmlString {
                        return new HtmlString(view('components.source-icon-view', [
                            'image'  => asset('storage/' . ($record->brand?->icon_path ?? '')),
                            'brand'  => $record->name,
                            'source' => TransactionSourceEnum::ACCOUNT,
                        ])->render());
                }),
            TextColumn::make('balance')
                ->label('')
                ->money('BRL')
                ->color(fn (Model $record) => MaskHelper::amountColor($record->balance))
                ->summarize(Sum::make()
                    ->money('BRL', locale: 'pt_BR', divideBy: 100)
                    ->label('Saldo total disponível'))
                ->getStateUsing(function (mixed $record): string {
                    return MaskHelper::covertIntToReal($record->balance);
                }),
        ];
    }
}
