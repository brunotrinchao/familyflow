<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Installment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class TransactionInfolistModal
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        TextEntry::make('amount')
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
                                return MaskHelper::covertIntToReal($record->amount);
                            })
                            ->size(TextSize::Large)
                    ])
                    ->columnSpanFull(),
                Grid::make(3)
                    ->visible(fn(Installment $record) => $record->transaction->installment_number > 1)
                    ->schema([
                        TextEntry::make('total_installment')
                            ->label('Parcelas')
                            ->state(function (Installment $record): string {
                                return "{$record->number} / {$record->transaction->installment_number}";
                            })
                            ->columnSpan(1),
                        TextEntry::make('total_paid')
                            ->label('Ja paguei')
                            ->state(function (Installment $record): string {
                                $amount = $record->transaction->installments()->where('status', InstallmentStatusEnum::PAID)->sum('amount');
                                return MaskHelper::covertIntToReal(abs($amount));
                            })
                            ->columnSpan(1),
                        TextEntry::make('total_amount')
                            ->label('Falta pagar')->state(function (Installment $record): string {
                                // 1. A transação pai contém todos os dados das parcelas
                                $transaction = $record->transaction;

                                if (!$transaction) return MaskHelper::covertIntToReal(0);

                                // 2. Somamos apenas as parcelas que ainda não foram pagas
                                // Nota: Certifique-se de que os Enums batem com sua lógica de "não pago"
                                $amountPending = $transaction->installments
                                    ->whereIn('status', [
                                        InstallmentStatusEnum::PENDING,
                                        InstallmentStatusEnum::POSTED,
                                        InstallmentStatusEnum::OVERDUE
                                        // Geralmente vencidas também entram aqui
                                    ])
                                    ->sum('amount');

                                // 3. Convertendo para Real (Ex: 1000 -> 10,00)
                                return MaskHelper::covertIntToReal(abs($amountPending));
                            })
                            ->columnSpan(1)
                    ])
                    ->extraAttributes([
                        'style' => "background-color: rgb(243 245 247 / 50%);",
                        'class' => 'p-2 rounded-lg'
                    ])
                    ->columnSpanFull(),
                Grid::make(2)
                    ->schema([
                        TextEntry::make('category')
                            ->label('Categoria')
                            ->getStateUsing(function (mixed $record): string {
                                return $record->transaction->category->name;
                            }),
                        TextEntry::make('account.name')
                            ->label('Conta')
                            ->visible(fn (Model $record) => $record->account_id),
                        TextEntry::make('transaction.creditCard.name')
                            ->label('Cartão')
                            ->visible(fn (Model $record) => $record->transaction->credit_card_id),
                    ])
                    ->columnSpanFull(),
                Grid::make(1)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Observação')
                            ->weight(FontWeight::Light)
                            ->getStateUsing(function (mixed $record): HtmlString {
                                return new HtmlString($record->transaction?->description);
                            })

                    ])
                    ->columnSpanFull(),
                //                Grid::make(1)
                //                    ->schema([
                //                        TextEntry::make('due_date')
                //                            ->label('Data')
                //                            ->date('d/m/Y'),
                //                    ])
                //                    ->columnSpanFull(),

            ]);
    }
}
