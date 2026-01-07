<?php

namespace App\Filament\Resources\CreditCards\Schemas;

use App\Enums\TransactionSourceEnum;
use App\Helpers\MaskHelper;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CreditCardTable
{
    public static function configure(): array
    {
        return [
            TextColumn::make('icon')
                ->label('')
                ->getStateUsing(function (mixed $record): HtmlString {
                    return new HtmlString(view('components.source-icon-view', [
                        'image'  => asset('storage/' . ($record->brand?->icon_path ?? '')),
                        'brand'  => null,
                        'source' => null,
                    ])->render());
                }),
            TextColumn::make('name')
                ->label('')
                ->searchable(),
            TextColumn::make('account')
                ->label('')
                ->getStateUsing(function (mixed $record): HtmlString {
                    return new HtmlString(view('components.source-icon-view', [
                        'image'  => asset('storage/' . ($record->account->brand?->icon_path ?? '')),
                        'brand'  => $record->account->name,
                        'source' => TransactionSourceEnum::ACCOUNT,
                    ])->render());
                }),
            TextColumn::make('last_four_digits')
                ->label('')
                ->fontFamily(FontFamily::Mono)
                ->getStateUsing(function (mixed $record): string {
                    return "xxxx xxxx xxxx {$record->last_four_digits}";
                })
                ->searchable(),
            TextColumn::make('limit')
                ->money('BRL')
                ->tooltip(fn ($record): HtmlString => new HtmlString(
                    "<b>Usado:</b> " . MaskHelper::covertIntToReal($record->used) .
                    "<br/><b>Restante:</b> " . MaskHelper::covertIntToReal($record->limit - $record->used))
                )
                ->summarize(
                    [
                        Sum::make()
                            ->money('BRL', locale: 'pt_BR', divideBy: 100)
                            ->label('Limite total disponível'),
                        Summarizer::make('used')
                            ->money('BRL', locale: 'pt_BR', divideBy: 100)
                            ->label('Limite total usado')
                            ->using(fn ($query) => $query->sum(\DB::raw('used'))),
                        Summarizer::make('remaining')
                            ->label('Total de Limite Restante')
                            ->money('BRL', divideBy: 100)
                            ->using(fn ($query) => $query->sum(\DB::raw('`limit` - used'))),
                    ])
                ->getStateUsing(function (mixed $record): string {
                    return MaskHelper::covertIntToReal($record->limit);
                }),
            //            TextColumn::make('used')
            //                ->money('BRL')
            //                ->summarize(Sum::make()
            //                    ->money('BRL', locale: 'pt_BR', divideBy: 100)
            //                    ->label('Limite total usado'))
            //                ->getStateUsing(function (mixed $record): string {
            //                    return MaskHelper::covertIntToReal($record->used);
            //                }),
            IconColumn::make('status')
                ->label('')
                ->boolean(),
        ];
    }
}
