<?php

namespace App\Filament\Resources\Installments\Utilities;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;

class InstallmentSummaries
{
    public static function getSummarizers(): array
    {
        return [
            // --- GRUPO: FLUXO REALIZADO (Dinheiro que já mudou de mão) ---

            Summarizer::make()
                ->label('Receita Realizada')
                ->extraAttributes(['class' => 'text-success-600 font-medium'])
                ->using(fn (Table $table): int => static::calculate($table, InstallmentStatusEnum::PAID, TransactionTypeEnum::INCOME))
                ->money('BRL', divideBy: 100),

            Summarizer::make()
                ->label('Despesa Realizada')
                ->extraAttributes(['class' => 'text-danger-600 font-medium'])
                ->using(fn (Table $table): int => static::calculate($table, InstallmentStatusEnum::PAID, TransactionTypeEnum::EXPENSE))
                ->money('BRL', divideBy: 100),

            // --- GRUPO: FLUXO PREVISTO (Compromissos agendados/faturas abertas) ---

            Summarizer::make()
                ->label('Receita Prevista')
                ->extraAttributes(['class' => 'text-gray-500 italic'])
                ->using(fn (Table $table): int => static::calculate($table, [InstallmentStatusEnum::PENDING, InstallmentStatusEnum::POSTED], TransactionTypeEnum::INCOME))
                ->money('BRL', divideBy: 100),

            Summarizer::make()
                ->label('Despesa Prevista')
                ->extraAttributes(['class' => 'text-gray-500 italic'])
                ->using(fn (Table $table): int => static::calculate($table, [InstallmentStatusEnum::PENDING, InstallmentStatusEnum::POSTED], TransactionTypeEnum::EXPENSE))
                ->money('BRL', divideBy: 100),

            // --- GRUPO: POSIÇÃO PATRIMONIAL ---

            Summarizer::make()
                ->label('Saldo em Contas')
//                ->description('Dinheiro disponível hoje')
                ->extraAttributes(['style' => 'font-weight: bold; border-top: 1px solid #eee'])
                ->using(fn (): int => static::getAccountBalance())
                ->money('BRL', divideBy: 100),

            Summarizer::make()
                ->label('Projeção Final')
//                ->description('Saldo + Tudo que falta no mês')
                ->extraAttributes(['class' => 'bg-gray-50 p-2 rounded-lg'])
                ->using(function (Table $table): int {
                    $saldoAtual = static::getAccountBalance();

                    // Soma algébrica de todos os registros da tabela (Incomes positivos e Expenses negativos)
                    $balancoMes = $table->getRecords()->sum(function ($record) {
                        return static::resolveRecordValue($record);
                    });

                    return $saldoAtual + $balancoMes;
                })
                ->money('BRL', divideBy: 100),
        ];
    }

    /**
     * Helper para filtrar e somar valores com base em status e tipo
     */
    private static function calculate(Table $table, $status, TransactionTypeEnum $type): int
    {
        $statuses = is_array($status) ? $status : [$status];

        return (int) $table->getRecords()->sum(function ($record) use ($statuses, $type) {
            // Se for uma Invoice (agrupador), somamos suas parcelas internas filtradas
            if ($record->is_invoice) {
                return $record->installments()
                    ->whereIn('status', $statuses)
                    ->whereHas('transaction', fn($q) => $q->where('type', $type))
                    ->sum('amount');
            }
            // Se for uma Installment individual
            $match = in_array($record->status, $statuses) && $record->transaction->type === $type;
            return $match ? $record->amount : 0;
        });
    }

    /**
     * Retorna o valor "cru" do registro respeitando o sinal (receita +, despesa -)
     */
    private static function resolveRecordValue($record): int
    {
        if ($record->is_invoice) {
            return $record->installments->sum('amount');
        }
        return $record->amount;
    }

    /**
     * Busca o saldo total das contas do tenant
     */
    private static function getAccountBalance(): int
    {
        return (int) Account::query()
            ->whereHas('familyUser', fn($q) => $q->where('family_id', Filament::getTenant()->id))
            ->sum('balance');
    }
}
