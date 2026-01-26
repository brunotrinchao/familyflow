<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Collections;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallmentGenerationService
{
    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }
    /**
     * Gera todas as parcelas e faturas a partir de uma transação.
     *
     * @param Transaction $transaction
     * @param int $installmentsCount
     * @param Carbon|null $startDate
     * @param int $startMonth
     * @return Collection
     * @throws Throwable
     */
    public function generate(
        Transaction $transaction,
        int $installmentsCount,
        ?Carbon $startDate = null,
        int $startMonth = 1
    ): Collection {
        try {
            return DB::transaction(function () use (
                $transaction,
                $installmentsCount,
                $startDate,
                $startMonth
            ) {
                $totalAmount = abs($transaction->amount);
                $installmentAmounts = $this->buildInstallmentAmounts(
                    $totalAmount,
                    $installmentsCount,
                    $transaction->type
                );

                $startDate = $startDate ?? Carbon::parse($transaction->date);
                $installments = collect();

                foreach ($installmentAmounts as $index => $amountPerInstallment) {
                    $i = $index + 1;
                    if ($i < $startMonth) {
                        continue;
                    }
                    $installment = $this->createInstallment(
                        $transaction,
                        $i,
                        $amountPerInstallment,
                        $startDate,
                        $startMonth
                    );

                    $installments->push($installment);
                }

                Log::info('Parcelas geradas com sucesso.', [
                    'transaction_id'      => $transaction->id,
                    'installments_count'  => $installments->count(),
                    'amount_per_installment' => $installmentAmounts[0] ?? 0,
                ]);

                return $installments;
            });
        } catch (Throwable $e) {
            Log::error('Erro ao gerar parcelas.', [
                'message'         => $e->getMessage(),
                'transaction_id'  => $transaction->id,
                'installments_count' => $installmentsCount,
                'trace'           => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Cria uma parcela individual.
     *
     * @param Transaction $transaction
     * @param int $number
     * @param int $amount
     * @param Carbon $startDate
     * @param int $startMonth
     * @return Installment
     */
    private function createInstallment(
        Transaction $transaction,
        int $number,
        int $amount,
        Carbon $startDate,
        int $startMonth
    ): Installment {
        $currentDueDate = $startDate->copy()->addMonths($number - $startMonth);
        $periodDate = $startDate->copy()->addMonths($number - 1)->startOfMonth();

        $installmentData = [
            'family_id'          => $transaction->familyUser->family_id,
            'transaction_id'     => $transaction->id,
            'number'             => $number,
            'amount'             => $amount,
            'due_date'           => $currentDueDate,
            'status'             => InstallmentStatusEnum::PENDING,
        ];

        if ($transaction->credit_card_id) {
            return $this->createCreditCardInstallment(
                $transaction,
                $installmentData,
                $periodDate,
                $amount
            );
        }

        if ($transaction->account_id) {
            return $this->createAccountInstallment(
                $transaction,
                $installmentData,
                $amount
            );
        }

        throw new \InvalidArgumentException(
            'Transação deve ter credit_card_id ou account_id definido.'
        );
    }

    /**
     * Cria parcela para cartão de crédito.
     *
     * @param Transaction $transaction
     * @param array $installmentData
     * @param Carbon $periodDate
     * @param int $amount
     * @return Installment
     */
    private function createCreditCardInstallment(
        Transaction $transaction,
        array $installmentData,
        Carbon $periodDate,
        int $amount
    ): Installment {
        $invoice = Invoice::firstOrCreate(
            [
                'family_id'      => $transaction->familyUser->family_id,
                'credit_card_id' => $transaction->credit_card_id,
                'period_date'    => $periodDate,
            ],
            [
                'total_amount' => 0,
                'status'       => InvoiceStatusEnum::PENDING,
            ]
        );

        // Atualiza limite usado do cartão
        if ($transaction->type === TransactionTypeEnum::EXPENSE) {
            $transaction->creditCard->increment('used', abs($amount));
            $invoice->increment('total_amount', abs($amount));
        } else {
            $transaction->creditCard->decrement('used', abs($amount));
            $invoice->decrement('total_amount', abs($amount));
        }

        $installmentData['invoice_id'] = $invoice->id;

        return Installment::create($installmentData);
    }

    /**
     * Cria parcela para conta bancária.
     *
     * @param Transaction $transaction
     * @param array $installmentData
     * @param int $amount
     * @return Installment
     */
    private function createAccountInstallment(
        Transaction $transaction,
        array $installmentData,
        int $amount
    ): Installment {
        $this->balanceService->applyAccountDelta($transaction->account, $amount);

        $installmentData['account_id'] = $transaction->account_id;

        return Installment::create($installmentData);
    }

    /**
     * Distribui o valor total entre as parcelas mantendo o total exato.
     *
     * @param int $totalAmount
     * @param int $installmentsCount
     * @param TransactionTypeEnum $type
     * @return array<int, int>
     */
    private function buildInstallmentAmounts(
        int $totalAmount,
        int $installmentsCount,
        TransactionTypeEnum $type
    ): array {
        $installmentsCount = max(1, $installmentsCount);
        $base = intdiv($totalAmount, $installmentsCount);
        $remainder = $totalAmount % $installmentsCount;
        $sign = $type === TransactionTypeEnum::EXPENSE ? -1 : 1;

        $amounts = [];
        for ($i = 1; $i <= $installmentsCount; $i++) {
            $amount = $base + ($i <= $remainder ? 1 : 0);
            $amounts[] = $amount * $sign;
        }

        return $amounts;
    }

    /**
     * Sincroniza o status das parcelas com a transação principal.
     *
     * @param Transaction $transaction
     * @param InstallmentStatusEnum $newStatus
     * @param bool $isCancellation
     * @return void
     * @throws Throwable
     */
    public function synchronizeStatus(
        Transaction $transaction,
        InstallmentStatusEnum $newStatus,
        bool $isCancellation = false
    ): void {
        try {
            DB::transaction(function () use ($transaction, $newStatus, $isCancellation) {
                $this->updateInstallmentsStatus(
                    $transaction->installments,
                    $newStatus,
                    $isCancellation
                );

                Log::info('Status das parcelas sincronizado.', [
                    'transaction_id'    => $transaction->id,
                    'new_status'        => $newStatus->value,
                    'is_cancellation'   => $isCancellation,
                    'installments_count' => $transaction->installments->count(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao sincronizar status das parcelas.', [
                'message'        => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'new_status'     => $newStatus->value,
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Sincroniza o status de parcelas específicas.
     *
     * @param Collection $installments
     * @param InstallmentStatusEnum $newStatus
     * @param bool $isCancellation
     * @return void
     * @throws Throwable
     */
    public function synchronizePartialStatus(
        Collection $installments,
        InstallmentStatusEnum $newStatus,
        bool $isCancellation = false
    ): void {
        try {
            DB::transaction(function () use ($installments, $newStatus, $isCancellation) {
                $this->updateInstallmentsStatus(
                    $installments,
                    $newStatus,
                    $isCancellation
                );

                Log::info('Status parcial das parcelas sincronizado.', [
                    'installments_count' => $installments->count(),
                    'new_status'         => $newStatus->value,
                    'is_cancellation'    => $isCancellation,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao sincronizar status parcial.', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza o status das parcelas e ajusta saldos.
     *
     * @param Collection $installments
     * @param InstallmentStatusEnum $newStatus
     * @param bool $isCancellation
     * @return void
     */
    private function updateInstallmentsStatus(
        Collection $installments,
        InstallmentStatusEnum $newStatus,
        bool $isCancellation
    ): void {
        foreach ($installments as $installment) {
            $installment->update(['status' => $newStatus]);

            $this->adjustBalancesForInstallment(
                $installment,
                $isCancellation
            );
        }
    }

    /**
     * Ajusta saldos de acordo com o status da parcela.
     *
     * @param Installment $installment
     * @param bool $isCancellation
     * @return void
     */
    private function adjustBalancesForInstallment(
        Installment $installment,
        bool $isCancellation
    ): void {
        $transaction = $installment->transaction;
        $amount = abs($installment->amount);
        $isExpense = $transaction->type === TransactionTypeEnum::EXPENSE;

        if ($transaction->credit_card_id) {
            $this->adjustCreditCardBalance(
                $transaction->creditCard,
                $amount,
                $isExpense,
                $isCancellation
            );
        }

        if ($transaction->account_id) {
            $this->adjustAccountBalance(
                $transaction->account,
                $amount,
                $isExpense,
                $isCancellation
            );
        }
    }

    /**
     * Ajusta saldo do cartão de crédito.
     *
     * @param $card
     * @param int $amount
     * @param bool $isExpense
     * @param bool $isCancellation
     * @return void
     */
    private function adjustCreditCardBalance(
        $card,
        int $amount,
        bool $isExpense,
        bool $isCancellation
    ): void {
        if ($isCancellation) {
            $isExpense
                ? $this->balanceService->applyCreditCardUsedDelta($card, -$amount)
                : $this->balanceService->applyCreditCardUsedDelta($card, $amount);
        } else {
            $isExpense
                ? $this->balanceService->applyCreditCardUsedDelta($card, $amount)
                : $this->balanceService->applyCreditCardUsedDelta($card, -$amount);
        }
    }

    /**
     * Ajusta saldo da conta bancária.
     *
     * @param $account
     * @param int $amount
     * @param bool $isExpense
     * @param bool $isCancellation
     * @return void
     */
    private function adjustAccountBalance(
        $account,
        int $amount,
        bool $isExpense,
        bool $isCancellation
    ): void {
        if ($isCancellation) {
            $isExpense
                ? $this->balanceService->applyAccountDelta($account, $amount)
                : $this->balanceService->applyAccountDelta($account, -$amount);
        } else {
            $isExpense
                ? $this->balanceService->applyAccountDelta($account, -$amount)
                : $this->balanceService->applyAccountDelta($account, $amount);
        }
    }

    /**
     * Atualiza uma parcela específica.
     *
     * @param array $data
     * @param Installment $installment
     * @return Installment
     * @throws Throwable
     */
    public function update(array $data, Installment $installment): Installment
    {
        try {
            return DB::transaction(function () use ($data, $installment) {
                $transaction = $installment->transaction;
                $updateMode = $data['update_mode'] ?? 'single';

                // Atualizar data da transação se fornecida
                if (isset($data['transaction']['date'])) {
                    $transaction->update(['date' => $data['transaction']['date']]);
                }

                // Determinar quais parcelas atualizar
                $installmentsToUpdate = $this->getInstallmentsToUpdate(
                    $transaction,
                    $installment,
                    $updateMode
                );

                $affectedInvoiceIds = collect();

                // Atualizar cada parcela
                foreach ($installmentsToUpdate as $inst) {
                    $affectedInvoiceIds->push($inst->invoice_id);

                    $updateData = $this->prepareInstallmentUpdateData(
                        $data,
                        $inst
                    );

                    if (!empty($updateData)) {
                        $inst->update($updateData);
                    }
                }

                // Recalcular total da transação
                $this->recalculateTransactionTotal($transaction, $data);

                // Recalcular totais das faturas afetadas
                $this->recalculateInvoiceTotals($affectedInvoiceIds);

                Log::info('Parcela atualizada com sucesso.', [
                    'installment_id'         => $installment->id,
                    'update_mode'            => $updateMode,
                    'affected_installments'  => $installmentsToUpdate->count(),
                ]);

                return $installment->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Erro ao atualizar parcela.', [
                'message'        => $e->getMessage(),
                'installment_id' => $installment->id,
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Determina quais parcelas devem ser atualizadas.
     *
     * @param Transaction $transaction
     * @param Installment $installment
     * @param string $updateMode
     * @return Collection
     */
    private function getInstallmentsToUpdate(
        Transaction $transaction,
        Installment $installment,
        string $updateMode
    ): \Illuminate\Support\Collection {
        return match ($updateMode) {
            'update_future' => $transaction->installments()
                ->where('number', '>=', $installment->number)
                ->get(),
            'update_all' => $transaction->installments,
            default => collect([$installment]),
        };
    }

    /**
     * Prepara os dados para atualização da parcela.
     *
     * @param array $data
     * @param Installment $installment
     * @return array
     */
    private function prepareInstallmentUpdateData(
        array $data,
        Installment $installment
    ): array {
        $updateData = [];

        if (isset($data['amount']) && $data['amount'] != $installment->amount) {
            $updateData['amount'] = $data['amount'];
        }

        if (isset($data['category_id']) && $data['category_id'] != $installment->category_id) {
            $updateData['category_id'] = $data['category_id'];
        }

        if (isset($data['status']) && $data['status']->value != $installment->status) {
            $updateData['status'] = $data['status'];
        }

        return $updateData;
    }

    /**
     * Recalcula o total da transação.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return void
     */
    private function recalculateTransactionTotal(
        Transaction $transaction,
        array $data
    ): void {
        $newTotalAmount = $transaction->installments()->sum('amount');

        $transaction->update([
            'amount'         => $newTotalAmount,
            'category_id'    => $data['category_id'] ?? $transaction->category_id,
            'credit_card_id' => $data['credit_card_id'] ?? $transaction->credit_card_id,
        ]);
    }

    /**
     * Recalcula os totais das faturas afetadas.
     *
     * @param Collections $invoiceIds
     * @return void
     */
    private function recalculateInvoiceTotals(Collections $invoiceIds): void
    {
        Invoice::whereIn('id', $invoiceIds->unique()->filter())
            ->get()
            ->each(function ($invoice) {
                $invoice->update([
                    'total_amount' => $invoice->installments()->sum('amount')
                ]);
            });
    }

    /**
     * Deleta parcelas.
     *
     * @param Installment|Collection $installments
     * @return void
     * @throws Throwable
     */
    public function delete($installments): void
    {
        $installments = $installments instanceof Installment
            ? collect([$installments])
            : $installments;

        if ($installments->isEmpty()) {
            return;
        }

        try {
            DB::transaction(function () use ($installments) {
                // Verificar se há parcelas pagas
                $paidInstallments = $installments->filter(function ($installment) {
                    return $installment->status === InstallmentStatusEnum::PAID;
                });

                if ($paidInstallments->isNotEmpty()) {
                    $paidNumbers = $paidInstallments->pluck('number')->implode(', #');
                    throw new \Exception(
                        "As parcelas (#{$paidNumbers}) já estão pagas e não podem ser removidas."
                    );
                }

                $transaction = $installments->first()->transaction;
                $affectedInvoiceIds = $installments->pluck('invoice_id')->unique();

                // Deletar parcelas
                foreach ($installments as $installment) {
                    $installment->delete();
                }

                // Verificar se restaram parcelas
                $remaining = $transaction->installments();

                if ($remaining->count() === 0) {
                    $transaction->delete();
                } else {
                    $transaction->update([
                        'amount'             => $remaining->sum('amount'),
                        'installment_number' => $remaining->count(),
                    ]);
                }

                // Recalcular totais das faturas
                $this->recalculateInvoiceTotals($affectedInvoiceIds);

                Log::info('Parcelas deletadas com sucesso.', [
                    'transaction_id'     => $transaction->id,
                    'deleted_count'      => $installments->count(),
                    'remaining_count'    => $remaining->count(),
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao deletar parcelas.', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
