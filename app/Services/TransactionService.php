<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionService
{
    private InvoicesService $invoiceService;
    private BalanceService $balanceService;

    public function __construct(InvoicesService $invoiceService, BalanceService $balanceService)
    {
        $this->invoiceService = $invoiceService;
        $this->balanceService = $balanceService;
    }

    /**
     * Cria uma nova transação.
     *
     * @param array $data
     * @return Transaction
     * @throws Throwable
     */
    public function create(array $data): Transaction
    {
        try {
            return DB::transaction(function () use ($data) {
                // Normalizar o valor (despesas sempre negativas, receitas positivas)

                $data['amount'] = $this->normalizeAmount(
                    $data['amount'],
                    $data['type']
                );

                // Criar a transação
                $transaction = Transaction::create($data);

                // Processar impactos financeiros
                $this->processFinancialImpact($transaction, $data);

                Log::info('Transação criada com sucesso.', [
                    'transaction_id'     => $transaction->id,
                    'type'               => $transaction->type->value,
                    'source'             => $transaction->source->value,
                    'amount'             => $transaction->amount,
                    'installment_number' => $transaction->installment_number,
                ]);

                return $transaction->fresh(['installments']);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao criar transação.', [
                'message' => $e->getMessage(),
                'data'    => $data,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma transação existente.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     * @throws Throwable
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        try {
            return DB::transaction(function () use ($transaction, $data) {
                // 1. Reverter impacto financeiro da versão anterior
                $this->revertFinancialImpact($transaction);

                // 2. Atualizar dados da transação
                $data['amount'] = $this->normalizeAmount(
                    $data['amount'],
                    $data['type']
                );

                $transaction->update($data);

                // 3. Deletar parcelas antigas
                $transaction->installments()->delete();

                // 4. Recarregar transação
                $transaction->fresh();

                // 5. Processar novo impacto financeiro
                $this->processFinancialImpact($transaction, $data);

                Log::info('Transação atualizada com sucesso.', [
                    'transaction_id' => $transaction->id,
                    'changes'        => $transaction->getChanges(),
                ]);

                return $transaction->fresh(['installments']);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao atualizar transação.', [
                'message'        => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'data'           => $data,
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Deleta uma transação.
     *
     * @param Transaction $transaction
     * @return bool
     * @throws Throwable
     */
    public function delete(Transaction $transaction): bool
    {
        try {
            return DB::transaction(function () use ($transaction) {
                // 1. Verificar se pode deletar
                if ($transaction->status === TransactionStatusEnum::PAID) {
                    throw new \Exception(
                        'Não é possível deletar transação já paga.'
                    );
                }

                // 2. Reverter impactos financeiros
                $this->revertFinancialImpact($transaction);

                // 3. Deletar parcelas
                $transaction->installments()->delete();

                // 4. Deletar transação (soft delete)
                $deleted = $transaction->delete();

                Log::info('Transação deletada com sucesso.', [
                    'transaction_id' => $transaction->id,
                    'type'           => $transaction->type->value,
                ]);

                return $deleted;
            });
        } catch (Throwable $e) {
            Log::error('Erro ao deletar transação.', [
                'message'        => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Normaliza o valor da transação baseado no tipo.
     *
     * @param int|float $amount
     * @param TransactionTypeEnum $type
     * @return int
     */
    private function normalizeAmount($amount, TransactionTypeEnum $type): int
    {
        $amount = abs((int)$amount);

        return $type === TransactionTypeEnum::EXPENSE ? -$amount : $amount;
    }

    /**
     * Processa os impactos financeiros da transação.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return void
     */
    private function processFinancialImpact(Transaction $transaction, array $data): void
    {
        if ($transaction->type === TransactionTypeEnum::TRANSFER) {
            $this->processTransferImpact($transaction);
            return;
        }

        match ($transaction->source) {
            TransactionSourceEnum::ACCOUNT => $this->processAccountImpact($transaction),
            TransactionSourceEnum::CREDIT_CARD => $this->processInstallments(
                $transaction,
                $data['invoice'] ?? null
            ),
        };
    }

    /**
     * Processa impacto em conta bancária.
     *
     * @param Transaction $transaction
     * @return void
     */
    private function processAccountImpact(Transaction $transaction): void
    {
        $account = $transaction->account;

        if (!$account) {
            throw new \InvalidArgumentException('Transação de conta requer account_id.');
        }

        $date = Carbon::parse($transaction->date);
        $status = $transaction->status === TransactionStatusEnum::PAID
            ? InstallmentStatusEnum::PAID
            : InstallmentStatusEnum::PENDING;

        // Criar parcela única
        Installment::create([
            'number'         => 1,
            'amount'   => $transaction->amount,
            'due_date'       => $date,
            'status'         => $status,
            'transaction_id' => $transaction->id,
            'family_id'      => $transaction->familyUser->family_id,
            'account_id'     => $account->id,
        ]);

        // Atualizar saldo se transação já está paga ou compensada
        if (in_array($transaction->status, [
            TransactionStatusEnum::PAID,
            TransactionStatusEnum::CLEARED
        ])) {
            $this->balanceService->applyAccountDelta($account, $transaction->amount);
        }
    }

    /**
     * Processa transferência entre contas.
     *
     * @param Transaction $transaction
     * @return void
     */
    private function processTransferImpact(Transaction $transaction): void
    {
        if (!$transaction->account_id || !$transaction->destination_account_id) {
            throw new \InvalidArgumentException(
                'Transferência requer account_id e destination_account_id.'
            );
        }

        // Atualizar título da transação
        $transaction->update([
            'title' => sprintf(
                "Transferência de: %s para: %s",
                $transaction->account->name,
                $transaction->destinationAccount->name
            )
        ]);

        $familyId = $transaction->familyUser->family_id;
        $amount = abs($transaction->amount);

        // 1. Parcela de SAÍDA (origem)
        Installment::create([
            'number'         => 1,
            'amount'   => -$amount,
            'due_date'       => $transaction->date,
            'status'         => InstallmentStatusEnum::PAID,
            'transaction_id' => $transaction->id,
            'family_id'      => $familyId,
            'account_id'     => $transaction->account_id,
        ]);

        // 2. Parcela de ENTRADA (destino)
        Installment::create([
            'number'         => 1,
            'amount'   => $amount,
            'due_date'       => $transaction->date,
            'status'         => InstallmentStatusEnum::PAID,
            'transaction_id' => $transaction->id,
            'family_id'      => $familyId,
            'account_id'     => $transaction->destination_account_id,
        ]);

        // 3. Atualizar saldos
        $this->balanceService->applyAccountDelta($transaction->account, -$amount);
        $this->balanceService->applyAccountDelta($transaction->destinationAccount, $amount);
    }

    /**
     * Processa parcelas de cartão de crédito.
     *
     * @param Transaction $transaction
     * @param string|null $invoiceDate
     * @return void
     */
    private function processInstallments(
        Transaction $transaction,
        ?string $invoiceDate = null
    ): void {
        if (!$transaction->credit_card_id) {
            throw new \InvalidArgumentException(
                'Transação de cartão requer credit_card_id.'
            );
        }

        $card = $transaction->creditCard;
        $installmentAmounts = $this->buildInstallmentAmounts(
            $transaction->amount,
            $transaction->installment_number
        );
        $familyId = $transaction->familyUser->family_id;

        // Incrementar limite usado do cartão
        $this->balanceService->applyCreditCardUsedDelta($card, abs($transaction->amount));

        $startDate = $invoiceDate
            ? Carbon::parse($invoiceDate)
            : Carbon::parse($transaction->date);

        // Criar parcelas
        foreach ($installmentAmounts as $index => $installmentAmount) {
            $i = $index + 1;
            $dueDate = $startDate->copy()
                ->addMonths($i - 1)
                ->startOfMonth()
                ->day($card->due_day);

            // Obter ou criar fatura
            $invoice = $this->invoiceService->getOrCreateInvoice($card, $dueDate);
            $invoice->increment('total_amount', abs($installmentAmount));

            // Criar parcela
            Installment::create([
                'number'         => $i,
                'amount'         => $installmentAmount,
                'due_date'       => $dueDate,
                'status'         => InstallmentStatusEnum::POSTED,
                'transaction_id' => $transaction->id,
                'family_id'      => $familyId,
                'invoice_id'     => $invoice->id,
            ]);
        }
    }

    /**
     * Reverte os impactos financeiros de uma transação.
     *
     * @param Transaction $transaction
     * @return void
     */
    private function revertFinancialImpact(Transaction $transaction): void
    {
        $amount = abs($transaction->amount);

        // Reverter impactos de conta bancária
        if ($transaction->source === TransactionSourceEnum::ACCOUNT) {
            $this->revertAccountImpact($transaction, $amount);
        }

        // Reverter impactos de cartão de crédito
        if ($transaction->source === TransactionSourceEnum::CREDIT_CARD) {
            $this->revertCreditCardImpact($transaction, $amount);
        }

        // Reverter transferências
        if ($transaction->type === TransactionTypeEnum::TRANSFER) {
            $this->revertTransferImpact($transaction, $amount);
        }
    }

    /**
     * Reverte impacto em conta bancária.
     *
     * @param Transaction $transaction
     * @param int $amount
     * @return void
     */
    private function revertAccountImpact(Transaction $transaction, int $amount): void
    {
        if (!$transaction->account) {
            return;
        }

        $validStatuses = [
            TransactionStatusEnum::PAID,
            TransactionStatusEnum::CLEARED
        ];

        if (!in_array($transaction->status, $validStatuses)) {
            return;
        }

        // Reverter saldo
        match ($transaction->type) {
            TransactionTypeEnum::EXPENSE => $this->balanceService->applyAccountDelta($transaction->account, $amount),
            TransactionTypeEnum::INCOME => $this->balanceService->applyAccountDelta($transaction->account, -$amount),
            default => null,
        };
    }

    /**
     * Reverte impacto de cartão de crédito.
     *
     * @param Transaction $transaction
     * @param int $amount
     * @return void
     */
    private function revertCreditCardImpact(Transaction $transaction, int $amount): void
    {
        $card = $transaction->creditCard;

        if (!$card) {
            return;
        }

        // Liberar limite usado
        $this->balanceService->applyCreditCardUsedDelta($card, -$amount);

        // Reverter valores das faturas
        foreach ($transaction->installments as $installment) {
            if ($installment->invoice_id) {
                $installment->invoice->decrement(
                    'total_amount',
                    abs($installment->amount)
                );
            }
        }
    }

    /**
     * Reverte impacto de transferência.
     *
     * @param Transaction $transaction
     * @param int $amount
     * @return void
     */
    private function revertTransferImpact(Transaction $transaction, int $amount): void
    {
        if (!$transaction->account || !$transaction->destination_account_id) {
            return;
        }

        // Devolve dinheiro para conta origem
        $this->balanceService->applyAccountDelta($transaction->account, $amount);

        // Remove dinheiro da conta destino
        $destAccount = $transaction->destinationAccount;
        if ($destAccount) {
            $this->balanceService->applyAccountDelta($destAccount, -$amount);
        }
    }

    /**
     * Distribui o valor total pelas parcelas, mantendo o total exato.
     *
     * @param int $totalAmount
     * @param int $installmentsCount
     * @return array<int, int>
     */
    private function buildInstallmentAmounts(int $totalAmount, int $installmentsCount): array
    {
        $installmentsCount = max(1, $installmentsCount);
        $sign = $totalAmount < 0 ? -1 : 1;
        $absoluteTotal = abs($totalAmount);

        $base = intdiv($absoluteTotal, $installmentsCount);
        $remainder = $absoluteTotal % $installmentsCount;

        $amounts = [];
        for ($i = 1; $i <= $installmentsCount; $i++) {
            $amount = $base + ($i <= $remainder ? 1 : 0);
            $amounts[] = $amount * $sign;
        }

        return $amounts;
    }
}
