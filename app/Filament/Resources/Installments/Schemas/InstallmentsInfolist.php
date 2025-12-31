<?php

namespace App\Filament\Resources\Installments\Schemas;

use App\Enums\TransactionStatusEnum;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;

class InstallmentsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        TextEntry::make('transaction.description')
                            ->hiddenLabel()
                            ->alignCenter()
                            ->weight(FontWeight::Bold)
                            ->extraAttributes(['style' => 'font-size: x-large'])
                            ->getStateUsing(function (mixed $record): string {
                                return $record->transaction->description;
                            })

                    ])
                    ->columnSpanFull(),
                Grid::make(1)
                    ->schema([
                        TextEntry::make('amount_cents')
                            ->label('Valor')
                            ->hiddenLabel()
                            ->color(function (Model $record) {
                                return $record->amount_cents > 0 ? Color::Green : Color::Red;
                            })
                            ->weight(FontWeight::Bold)
                            ->money('BRL')
                            ->alignCenter()
                            ->extraAttributes(['style' => 'font-size: xx-large'])
                            ->getStateUsing(function (mixed $record): string {
                                return MaskHelper::covertIntToReal($record->amount_cents);
                            })
                            ->size(TextSize::Large)
                    ])
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        TextEntry::make('transaction.category')
                            ->label('Categoria')
                            ->getStateUsing(function (mixed $record): string {
                                return $record->transaction->category->name;
                            }),
                        TextEntry::make('transaction.account.name')
                            ->label('Conta'),
                    ])
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        TextEntry::make('due_date')
                            ->label('Data')
                            ->date('d/m/Y'),
                        TextEntry::make('installment_posted')
                            ->label('Parcelas Pagas')
                            ->getStateUsing(fn (Model $record) => Installment::where('invoice_id', $record->invoice_id)
                                ->where('status', TransactionStatusEnum::PAID)
                                ->count()
                            )
                            ->suffix(fn (Model $record) => ' de ' . $record->transaction->installment_number)
                            ->color('success')
                        ->visible(fn (Model $record) => $record->transaction->installment_number > 1),
                    ])
                    ->columnSpanFull(),

            ]);
    }
}
