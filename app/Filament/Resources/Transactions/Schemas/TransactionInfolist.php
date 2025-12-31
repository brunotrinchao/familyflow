<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\TransactionStatusEnum;
use App\Helpers\MaskHelper;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // === COLUNA ESQUERDA (2/3) ===
                        Section::make('')
                            ->schema([
                                // Linha 1: Descrição (Full Width)
                                TextEntry::make('description')
                                    ->label('Descrição')
                                    ->columnSpanFull(),
                                // Ocupa a largura total da seção

                                // Linha 2: Dados Financeiros e Classificação (Layout interno de 3 colunas)
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('amount')
                                            ->label('Valor')
                                            ->money('BRL')
                                            ->getStateUsing(function ($record) {

                                                return MaskHelper::covertIntToReal($record->amount);
                                            })
                                            ->size(TextSize::Large),
                                        // Destaque para o valor
                                        TextEntry::make('date')
                                            ->label('Data')
                                            ->date('d/m/Y'),
                                        TextEntry::make('type')
                                            ->label('Tipo'),
                                    ]),

                                // Linha 3: Metadados
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('category.name')
                                            ->label('Categoria'),
                                        TextEntry::make('source')
                                            ->label('Origem'),
                                    ]),
                            ])
                            ->columnSpan(2),
                        // Ocupa 2 das 3 colunas principais

                        // === COLUNA DIREITA (1/3) ===
                        Section::make('')
                            ->schema([

                                TextEntry::make('installment_posted')
                                    ->label('Parcelas Pagas')
                                    ->getStateUsing(fn (Model $record) => $record->transactionSeries()
                                        ->where('status', TransactionStatusEnum::POSTED)
                                        ->count()
                                    )
                                    // Exibe "2 de 5"
                                    ->suffix(fn (Model $record) => ' de ' . $record->installment_number)
                                    ->color('success'),

                                // Campo para o status principal da transação, se aplicável
                                TextEntry::make('status')
                                    ->label('Status Global')
                                    ->badge(),

                            ])
                            ->columns(1) // Garante que os campos fiquem empilhados verticalmente
                            ->columnSpan(1),
                        // Ocupa 1 das 3 colunas principais
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
