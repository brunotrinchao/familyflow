<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallmentGenerationService
{
    /**
     * Gera todos os Installments e Invoices a partir de uma Transaction (Compra Pai).
     */
    public function generate(Transaction $transaction, int $installmentsCount, ?Carbon $startDate,
                             int         $startMonth = 1, ?Invoice $invoice = null): Collection
    {
        $totalAmount = abs($transaction->amount);
        $amountPerInstallment = $this->calcValue(intval($totalAmount / $installmentsCount), $transaction->type);

        return DB::transaction(function () use (
            $startDate, $transaction, $installmentsCount, $startMonth, $invoice, $amountPerInstallment
        ) {
            $instaments = new Collection();

            for ($i = $startMonth; $i <= $installmentsCount; $i++) {

                $currentDueDate = $startDate->copy()->addMonths($i - $startMonth);
                $periodDate = $startDate->copy()->addMonths($i - 1)->startOfMonth();


                if (!empty($transaction->credit_card_id)) {

                    $invoice = Invoice::firstOrCreate(
                        [
                            'family_id'      => $transaction->family_id,
                            'credit_card_id' => $transaction->credit_card_id,
                            'period_date'    => $periodDate,
                        ],
                        [
                            'total_amount_cents' => 0,
                            'status'             => TransactionStatusEnum::PENDING,
                        ]
                    );

                    if ($transaction->type == TransactionTypeEnum::EXPENSE) {
                        // Se é despesa, credit_used aumenta (fica mais negativo)
                        // E o total da fatura aumenta (valor negativo no seu sistema)
                        $transaction->creditCard->increment('credit_used', $amountPerInstallment);

                        $invoice->increment('total_amount_cents', $amountPerInstallment);
                    } else {
                        $transaction->creditCard->decrement('credit_used', $amountPerInstallment);
                        $invoice->decrement('total_amount_cents', $amountPerInstallment);
                    }

                    $invoice->fresh();

                    $installmentItem = Installment::create([
                        'family_id'          => $transaction->family_id,
                        'transaction_id'     => $transaction->id,
                        'invoice_id'         => $invoice->id,
                        'installment_number' => $i,
                        'amount_cents'       => $amountPerInstallment,
                        'due_date'           => $currentDueDate,
                        'status'             => TransactionStatusEnum::PENDING,
                    ]);

                } else if ($transaction->account_id) {
                    $transaction->account->increment('balance', $amountPerInstallment);
                    $installmentItem = Installment::create([
                        'family_id'          => $transaction->family_id,
                        'transaction_id'     => $transaction->id,
                        'account_id'         => $transaction->account_id,
                        'installment_number' => $i,
                        'amount_cents'       => $amountPerInstallment,
                        'due_date'           => $periodDate,
                        'status'             => TransactionStatusEnum::PENDING,
                    ]);

                }

                $instaments->push($installmentItem);
            }

            return $instaments;
        });
    }


    /**
     * Sincroniza o status da Transaction principal para seus Installments associados.
     *
     * @param Transaction $transaction A transação pai.
     * @param TransactionStatusEnum $newStatus O novo status a ser aplicado.
     * @throws Throwable
     */
    public function synchronizeStatus(Transaction $transaction, TransactionStatusEnum $newStatus,
                                      bool        $isCancellation = false): void
    {
        try {
            $this->resolveUpdate($transaction->installments, $newStatus, $isCancellation);

            Log::info("Status of installments for Transaction #{$transaction->id} synchronized to {$newStatus->value}.");

        } catch (Throwable $e) {
            Log::error('Falha na sincronização de status de Installments.', [
                'transaction_id' => $transaction->id,
                'new_status'     => $newStatus->value,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function synchronizePartialStatus(Collection $installments, TransactionStatusEnum $newStatus,
                                             bool       $isCancellation = false): void
    {
        try {
            $this->resolveUpdate($installments, $newStatus, $isCancellation);

            Log::info("Status of installments synchronized to {$newStatus->value}.");

        } catch (Throwable $e) {
            Log::error('Falha na sincronização de status de Installments.', [
                'transaction_id' => $installments->first()->transaction->id,
                'new_status'     => $newStatus->value,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function calcValue(int $value, TransactionTypeEnum $type)
    {
        return $value * ($type == TransactionTypeEnum::EXPENSE ? -1 : 1);
    }

    /**
     * @param Transaction $transaction
     * @param TransactionStatusEnum $newStatus
     * @param bool $isCancellation
     * @return void
     */
    public function resolveUpdate(Collection $installments, TransactionStatusEnum $newStatus,
                                  bool       $isCancellation): void
    {
        foreach ($installments as $installment) {
            $amountCents = $installment->amount_cents;

            $installment->update(['status' => $newStatus]);

            $transaction = $installment->transaction;

            if ($transaction->credit_card_id) {
                $card = $transaction->creditCard;

                $isExpense = $transaction->type === TransactionTypeEnum::EXPENSE;

                if ($isCancellation) {
                    // No cancelamento de um pagamento de despesa, o limite deve voltar a ser "usado"
                    $isExpense ? $card->decrement('credit_used', $amountCents)
                        : $card->increment('credit_used', $amountCents);
                } else {
                    // No pagamento normal de uma despesa, liberamos o limite
                    $isExpense ? $card->increment('credit_used', $amountCents)
                        : $card->decrement('credit_used', $amountCents);
                }
            }

            if ($transaction->account_id) {
                $account = $transaction->account;
                $isExpense = $transaction->type === TransactionTypeEnum::EXPENSE;

                if ($isCancellation) {
                    // Se cancelo o pagamento de uma despesa, o dinheiro volta para a conta (+)
                    $isExpense ? $account->increment('balance', $amountCents)
                        : $account->decrement('balance', $amountCents);
                } else {
                    // Pagamento normal de despesa remove dinheiro da conta (-)
                    $isExpense ? $account->decrement('balance', $amountCents)
                        : $account->increment('balance', $amountCents);
                }
            }
        }
    }

    public function update(array $data, Installment $record): Installment
    {
        $transaction = $record->transaction;
        $updateMode = $data['update_mode'] ?? null;

        // 1. Atualizar Data da Transaction (Se alterada)
        if (isset($data['transaction']['date'])) {
            $transaction->update(['date' => $data['transaction']['date']]);
        }

        // 2. Lógica de Atualização baseada no Modo Selecionado
        $installmentsToUpdate = match ($updateMode) {
            'update_future' => $transaction->installments()->where('number', '>=', $record->number)->get(),
            'update_all' => $transaction->installments,
            default => collect([$record]),
        };

        $affectedInvoiceIds = collect();
        // 3. Atualizar as Parcelas
        foreach ($installmentsToUpdate as $installment) {
            $affectedInvoiceIds->push($installment->invoice_id);

            $updateData = [
                'amount'      => $data['amount'],
                'category_id' => $data['category_id'],
            ];

            if($data['amount'] != $installment->amount) {
                $updateData['amount'] = $data['amount'];
            }

            if($data['category_id'] != $installment->category_id) {
                $updateData['category_id'] = $data['category_id'];
            }

            if($data['status']->value != $installment->status) {
                $updateData['status'] = $data['status'];
            }

            $installment->update($updateData);
        }

        // 4. Recalcular o valor total da Transaction pai
        // O valor total da transação deve ser a soma de todas as suas parcelas
        $newTotalAmount = $transaction->installments()->sum('amount');

        $transaction->update([
            'amount'         => $newTotalAmount,
            'category_id'    => $data['category_id'],
            'credit_card_id' => $data['credit_card_id'] ?? $transaction->credit_card_id,
        ]);

        Invoice::whereIn('id', $affectedInvoiceIds->unique())->get()->each(function ($invoice) {
            $invoice->update([
                'total_amount' => $invoice->installments()->sum('amount')
            ]);
        });
        return $record->refresh();
    }

    public function delete(Installment|Collection $installments): void
    {

        $installments = $installments instanceof Installment ? collect([$installments]) : $installments;

        try {
            DB::beginTransaction();

            if ($installments->isEmpty()) {
                return;
            }

            $transaction = $installments->first()->transaction;

            $affectedInvoiceIds = $installments->pluck('invoice_id')->unique();

            if ($installments->contains('status', InstallmentStatusEnum::PAID)) {
                $paidNumbers = $installments->where('status', InstallmentStatusEnum::PAID)
                    ->pluck('number')
                    ->implode(', #');

                throw new \Exception("Ação cancelada: As parcelas (#{$paidNumbers}) já estão pagas e não podem ser removidas.");
            }

            // 2. Execução segura
            foreach ($installments as $installment) {
                $installment->delete();
            }

            $remaining = $transaction->installments();

            if ($remaining->count() === 0) {
                $transaction->delete();
            } else {
                $transaction->update([
                    'amount'             => $remaining->sum('amount'),
                    'installment_number' => $remaining->count(),
                ]);
            }

            Invoice::whereIn('id', $affectedInvoiceIds)->get()->each(function ($invoice) {
                $invoice->update([
                    'total_amount' => $invoice->installments()->sum('amount')
                ]);
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // REPORTE: Loga o erro para o desenvolvedor
            report($e);
            // RELANCE: Permite que a Action capture o erro e mostre a notificação
            throw $e;
        }
    }
}
