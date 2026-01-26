<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\StatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoicesService
{
    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }
    /**
     * Recalcula o valor total da fatura.
     *
     * @param Invoice $invoice
     * @return void
     */
    public function recalculateTotal(Invoice $invoice): void
    {
        try {
            $total = $invoice->installments()->sum('amount');
            $invoice->update(['total_amount' => $total]);

            Log::info('Total da fatura recalculado.', [
                'invoice_id' => $invoice->id,
                'new_total'  => $total,
            ]);
        } catch (Throwable $e) {
            Log::error('Erro ao recalcular total da fatura.', [
                'message'    => $e->getMessage(),
                'invoice_id' => $invoice->id,
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Fecha a fatura (muda de Aberta para Fechada/Pendente).
     *
     * @param Invoice $invoice
     * @return void
     * @throws Throwable
     */
    public function closeInvoice(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatusEnum::OPEN) {
            return;
        }

        try {
            DB::transaction(function () use ($invoice) {
                $invoice->update(['status' => InvoiceStatusEnum::PENDING]);

                // Atualiza as parcelas para "Lançadas na Fatura"
                $invoice->installments()
                    ->where('status', InstallmentStatusEnum::PENDING)
                    ->update(['status' => InstallmentStatusEnum::POSTED]);

                Log::info('Fatura fechada.', [
                    'invoice_id'   => $invoice->id,
                    'period_date'  => $invoice->period_date,
                    'total_amount' => $invoice->total_amount,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao fechar fatura.', [
                'message'    => $e->getMessage(),
                'invoice_id' => $invoice->id,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Busca ou cria a fatura para um cartão em uma data específica.
     *
     * @param CreditCard $card
     * @param Carbon $date
     * @return Invoice
     */
    public function getOrCreateInvoice(CreditCard $card, Carbon $date): Invoice
    {
        try {
            return Invoice::firstOrCreate(
                [
                    'credit_card_id' => $card->id,
                    'period_date'    => $date->copy()->startOfMonth(),
                    'family_id'      => $card->familyUser->family_id,
                ],
                [
                    'total_amount' => 0,
                    'status'       => InvoiceStatusEnum::OPEN,
                ]
            );
        } catch (Throwable $e) {
            Log::error('Erro ao buscar/criar fatura.', [
                'message'        => $e->getMessage(),
                'credit_card_id' => $card->id,
                'date'           => $date->format('Y-m-d'),
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Consolida todas as faturas abertas de uma família que já passaram da data de fechamento.
     *
     * @param int $familyId
     * @return int Número de faturas fechadas
     */
    public function closeAllExpiredInvoices(int $familyId): int
    {
        try {
            $today = Carbon::now();

            $invoices = Invoice::where('family_id', $familyId)
                ->where('status', InvoiceStatusEnum::OPEN)
                ->whereHas('creditCard', function ($query) use ($today) {
                    $query->where('closing_day', '<=', $today->day);
                })
                ->get();

            $closedCount = 0;
            foreach ($invoices as $invoice) {
                $this->closeInvoice($invoice);
                $closedCount++;
            }

            Log::info('Faturas expiradas fechadas.', [
                'family_id'    => $familyId,
                'closed_count' => $closedCount,
            ]);

            return $closedCount;

        } catch (Throwable $e) {
            Log::error('Erro ao fechar faturas expiradas.', [
                'message'   => $e->getMessage(),
                'family_id' => $familyId,
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Confirma o pagamento de uma fatura.
     *
     * @param Invoice $invoice
     * @param Account $account
     * @param int|null $amount
     * @return Payment
     * @throws Throwable
     */
    public function confirmPayment(
        Invoice $invoice,
        Account $account,
        ?int $amount = null
    ): Payment {
        try {
            return DB::transaction(function () use ($invoice, $account, $amount) {
                $totalInvoiceAmount = abs($invoice->total_amount);
                $amountPaid = $amount ?? $totalInvoiceAmount;

                // Validar saldo da conta
                if ($account->balance < $amountPaid) {
                    throw new \Exception(
                        'Saldo insuficiente na conta para realizar o pagamento.'
                    );
                }

                // 1. Registrar o pagamento
                $payment = Payment::create([
                    'amount'         => $amountPaid,
                    'paid_at'        => now(),
                    'invoice_id'     => $invoice->id,
                    'account_id'     => $account->id,
                    'status'         => \App\Enums\PaymentStatusEnum::POSTED,
                ]);

                // 2. Movimentação financeira
                $this->balanceService->applyAccountDelta($account, -$amountPaid);
                $this->balanceService->applyCreditCardUsedDelta($invoice->creditCard, -$amountPaid);

                // 3. Atualizar status da fatura e parcelas
                if ($amountPaid < $totalInvoiceAmount) {
                    $remainingBalance = $totalInvoiceAmount - $amountPaid;
                    $this->rollOverRemainingBalance($invoice, $remainingBalance);
                    $invoice->update(['status' => InvoiceStatusEnum::PARTIAL]);
                } else {
                    $invoice->update(['status' => InvoiceStatusEnum::PAID]);
                    $invoice->installments()->update([
                        'status' => InstallmentStatusEnum::PAID
                    ]);
                }

                Log::info('Pagamento de fatura confirmado.', [
                    'invoice_id'    => $invoice->id,
                    'account_id'    => $account->id,
                    'amount_paid'   => $amountPaid,
                    'total_invoice' => $totalInvoiceAmount,
                    'payment_id'    => $payment->id,
                ]);

                return $payment;
            });
        } catch (Throwable $e) {
            Log::error('Erro ao confirmar pagamento.', [
                'message'    => $e->getMessage(),
                'invoice_id' => $invoice->id,
                'account_id' => $account->id,
                'amount'     => $amount,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancela todos os pagamentos vinculados à fatura.
     *
     * @param Invoice $invoice
     * @return void
     * @throws Throwable
     */
    public function cancelPayments(Invoice $invoice): void
    {
        try {
            DB::transaction(function () use ($invoice) {
                $payments = $invoice->payments;

                foreach ($payments as $payment) {
                    // Estorna o saldo para a conta
                    $account = $payment->account ?? $payment->transaction?->account;
                    if ($account) {
                        $this->balanceService->applyAccountDelta(
                            $account,
                            $payment->amount
                        );
                    }

                    // Deleta o registro do pagamento
                    $payment->delete();
                }

                // Restaura o limite usado do cartão
                $this->balanceService->applyCreditCardUsedDelta(
                    $invoice->creditCard,
                    abs($invoice->total_amount)
                );

                // Volta a fatura para pendente
                $invoice->update(['status' => InvoiceStatusEnum::PENDING]);

                // Volta as parcelas para lançadas
                $invoice->installments()->update([
                    'status' => InstallmentStatusEnum::POSTED
                ]);

                Log::info('Pagamentos da fatura cancelados.', [
                    'invoice_id'      => $invoice->id,
                    'payments_count'  => $payments->count(),
                    'restored_amount' => $invoice->total_amount,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao cancelar pagamentos.', [
                'message'    => $e->getMessage(),
                'invoice_id' => $invoice->id,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Move o saldo não pago para a fatura do próximo mês.
     *
     * @param Invoice $currentInvoice
     * @param int $amount
     * @return void
     */
    private function rollOverRemainingBalance(Invoice $currentInvoice, int $amount): void
    {
        try {
            $nextMonthDate = $currentInvoice->period_date->copy()->addMonth();

            // Localiza ou cria a fatura do próximo mês
            $nextInvoice = $this->getOrCreateInvoice(
                $currentInvoice->creditCard,
                $nextMonthDate
            );

            // Incrementa o valor da próxima fatura
            $nextInvoice->increment('total_amount', $amount);

            Log::info('Saldo rolado para próxima fatura.', [
                'current_invoice_id' => $currentInvoice->id,
                'next_invoice_id'    => $nextInvoice->id,
                'rolled_amount'      => $amount,
            ]);

        } catch (Throwable $e) {
            Log::error('Erro ao rolar saldo para próxima fatura.', [
                'message'    => $e->getMessage(),
                'invoice_id' => $currentInvoice->id,
                'amount'     => $amount,
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Obtém faturas pendentes de uma família.
     *
     * @param int $familyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingInvoices(int $familyId)
    {
        return Invoice::where('family_id', $familyId)
            ->whereIn('status', [
                InvoiceStatusEnum::PENDING,
                InvoiceStatusEnum::OPEN,
                InvoiceStatusEnum::PARTIAL
            ])
            ->with(['creditCard', 'installments'])
            ->orderBy('period_date')
            ->get();
    }

    /**
     * Obtém faturas vencidas de uma família.
     *
     * @param int $familyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOverdueInvoices(int $familyId)
    {
        $today = Carbon::now();

        return Invoice::where('family_id', $familyId)
            ->whereIn('status', [
                InvoiceStatusEnum::PENDING,
                InvoiceStatusEnum::PARTIAL
            ])
            ->whereHas('creditCard', function ($query) use ($today) {
                $query->whereRaw(
                    'DATE_ADD(period_date, INTERVAL due_day DAY) < ?',
                    [$today]
                );
            })
            ->with(['creditCard', 'installments'])
            ->orderBy('period_date')
            ->get();
    }
}
