<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Account;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }
    /**
     * Processa o pagamento de uma fatura.
     *
     * @param Invoice $invoice
     * @param Account $account
     * @param int $amount
     * @return Payment
     * @throws Throwable
     */
    public function payInvoice(Invoice $invoice, Account $account, int $amount): Payment
    {
        try {
            return DB::transaction(function () use ($invoice, $account, $amount) {
                // Validações
                $this->validateInvoicePayment($invoice, $account, $amount);

                // Criar registro de pagamento
                $payment = Payment::create([
                    'amount'     => $amount,
                    'paid_at'    => now(),
                    'invoice_id' => $invoice->id,
                    'account_id' => $account->id,
                    'status'     => PaymentStatusEnum::POSTED,
                ]);

                // Debitar da conta
                $this->balanceService->applyAccountDelta($account, -$amount);

                // Liberar limite do cartão
                $this->balanceService->applyCreditCardUsedDelta($invoice->creditCard, -$amount);

                // Atualizar status da fatura e parcelas
                $this->updateInvoiceStatus($invoice, $amount);

                Log::info('Pagamento de fatura realizado.', [
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'account_id' => $account->id,
                    'amount'     => $amount,
                ]);

                return $payment;
            });
        } catch (Throwable $e) {
            Log::error('Erro ao pagar fatura.', [
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
     * Processa o pagamento de uma transação avulsa.
     *
     * @param Transaction $transaction
     * @param Account $account
     * @return Payment
     * @throws Throwable
     */
    public function payTransaction(Transaction $transaction, Account $account): Payment
    {
        try {
            return DB::transaction(function () use ($transaction, $account) {
                // Validações
                $this->validateTransactionPayment($transaction, $account);

                $amount = abs($transaction->amount);

                // Criar registro de pagamento
                $payment = Payment::create([
                    'amount'         => $amount,
                    'paid_at'        => now(),
                    'transaction_id' => $transaction->id,
                    'account_id'     => $account->id,
                    'status'         => PaymentStatusEnum::POSTED,
                ]);

                // Debitar da conta
                $this->balanceService->applyAccountDelta($account, -$amount);

                // Atualizar status da transação
                $transaction->update(['status' => TransactionStatusEnum::PAID]);

                // Atualizar status das parcelas
                $transaction->installments()->update([
                    'status' => InstallmentStatusEnum::PAID
                ]);

                Log::info('Pagamento de transação realizado.', [
                    'payment_id'     => $payment->id,
                    'transaction_id' => $transaction->id,
                    'account_id'     => $account->id,
                    'amount'         => $amount,
                ]);

                return $payment;
            });
        } catch (Throwable $e) {
            Log::error('Erro ao pagar transação.', [
                'message'        => $e->getMessage(),
                'transaction_id' => $transaction->id,
                'account_id'     => $account->id,
                'trace'          => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancela um pagamento.
     *
     * @param Payment $payment
     * @return void
     * @throws Throwable
     */
    public function cancelPayment(Payment $payment): void
    {
        try {
            DB::transaction(function () use ($payment) {
                // Validar se pode cancelar
                if ($payment->status === PaymentStatusEnum::CANCELLED) {
                    throw new \Exception('Pagamento já está cancelado.');
                }

                // Reverter saldo da conta
                if ($payment->invoice_id) {
                    $this->revertInvoicePayment($payment);
                } elseif ($payment->transaction_id) {
                    $this->revertTransactionPayment($payment);
                }

                // Marcar pagamento como cancelado
                $payment->update(['status' => PaymentStatusEnum::CANCELLED]);

                Log::info('Pagamento cancelado.', [
                    'payment_id'     => $payment->id,
                    'invoice_id'     => $payment->invoice_id,
                    'transaction_id' => $payment->transaction_id,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao cancelar pagamento.', [
                'message'    => $e->getMessage(),
                'payment_id' => $payment->id,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Valida se o pagamento da fatura pode ser realizado.
     *
     * @param Invoice $invoice
     * @param Account $account
     * @param int $amount
     * @return void
     * @throws \Exception
     */
    private function validateInvoicePayment(
        Invoice $invoice,
        Account $account,
        int $amount
    ): void {
        if ($invoice->status === InvoiceStatusEnum::PAID) {
            throw new \Exception('Fatura já está paga.');
        }

        if ($amount <= 0) {
            throw new \Exception('Valor do pagamento deve ser maior que zero.');
        }

        if ($account->balance < $amount) {
            throw new \Exception('Saldo insuficiente na conta.');
        }

        if ($amount > abs($invoice->total_amount)) {
            throw new \Exception('Valor do pagamento maior que o total da fatura.');
        }
    }

    /**
     * Valida se o pagamento da transação pode ser realizado.
     *
     * @param Transaction $transaction
     * @param Account $account
     * @return void
     * @throws \Exception
     */
    private function validateTransactionPayment(
        Transaction $transaction,
        Account $account
    ): void {
        if ($transaction->status === TransactionStatusEnum::PAID) {
            throw new \Exception('Transação já está paga.');
        }

        $amount = abs($transaction->amount);

        if ($account->balance < $amount) {
            throw new \Exception('Saldo insuficiente na conta.');
        }
    }

    /**
     * Atualiza o status da fatura após pagamento.
     *
     * @param Invoice $invoice
     * @param int $amountPaid
     * @return void
     */
    private function updateInvoiceStatus(Invoice $invoice, int $amountPaid): void
    {
        $totalAmount = abs($invoice->total_amount);

        if ($amountPaid >= $totalAmount) {
            // Pagamento total
            $invoice->update(['status' => InvoiceStatusEnum::PAID]);
            $invoice->installments()->update(['status' => InstallmentStatusEnum::PAID]);
        } else {
            // Pagamento parcial
            $invoice->update(['status' => InvoiceStatusEnum::PARTIAL]);
        }
    }

    /**
     * Reverte um pagamento de fatura.
     *
     * @param Payment $payment
     * @return void
     */
    private function revertInvoicePayment(Payment $payment): void
    {
        $invoice = $payment->invoice;

        $account = $payment->account ?? $payment->transaction?->account;
        if ($account) {
            $this->balanceService->applyAccountDelta($account, $payment->amount);
        }

        // Restaurar limite usado do cartão
        $this->balanceService->applyCreditCardUsedDelta($invoice->creditCard, $payment->amount);

        // Voltar fatura para pendente
        $invoice->update(['status' => InvoiceStatusEnum::PENDING]);

        // Voltar parcelas para lançadas
        $invoice->installments()->update(['status' => InstallmentStatusEnum::POSTED]);
    }

    /**
     * Reverte um pagamento de transação.
     *
     * @param Payment $payment
     * @return void
     */
    private function revertTransactionPayment(Payment $payment): void
    {
        $transaction = $payment->transaction;

        // Devolver saldo para a conta
        $account = $payment->account ?? $transaction->account;
        if ($account) {
            $this->balanceService->applyAccountDelta($account, $payment->amount);
        }

        // Voltar transação para pendente
        $transaction->update(['status' => TransactionStatusEnum::PENDING]);

        // Voltar parcelas para pendente
        $transaction->installments()->update([
            'status' => InstallmentStatusEnum::PENDING
        ]);
    }

    /**
     * Obtém o histórico de pagamentos de uma fatura.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInvoicePaymentHistory(Invoice $invoice)
    {
        return $invoice->payments()
            ->with(['account', 'transaction'])
            ->orderBy('paid_at', 'desc')
            ->get();
    }

    /**
     * Calcula o total pago de uma fatura.
     *
     * @param Invoice $invoice
     * @return int
     */
    public function getTotalPaid(Invoice $invoice): int
    {
        return $invoice->payments()
            ->where('status', PaymentStatusEnum::POSTED)
            ->sum('amount');
    }

    /**
     * Calcula o saldo devedor de uma fatura.
     *
     * @param Invoice $invoice
     * @return int
     */
    public function getRemainingBalance(Invoice $invoice): int
    {
        $totalAmount = abs($invoice->total_amount);
        $totalPaid = $this->getTotalPaid($invoice);

        return max(0, $totalAmount - $totalPaid);
    }
}
