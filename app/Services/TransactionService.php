<?php

namespace App\Services;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Stripe\Service\InvoiceService;
use Throwable;

class TransactionService
{
    /**
     * Cria uma transação e processa seus impactos financeiros.
     */
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {

            $isExpense = $data['type'] === TransactionTypeEnum::EXPENSE;

            $rawAmount = abs($data['amount']);
            $data['amount'] = $isExpense ? -$rawAmount : $rawAmount;


            $transaction = Transaction::create($data);

            match ($transaction->source) {
                TransactionSourceEnum::ACCOUNT => $this->processAccountImpact($transaction),

                TransactionSourceEnum::CREDIT_CARD => $this->processInstallments(
                    $transaction,
                    $data['invoice'] ?? null
                ),

                default => throw new \Exception("Fonte de transação desconhecida"),
            };

            return $transaction;
        });
    }

    /**
     * Processa a entrada/saída de dinheiro da conta.
     */
    private function processAccountImpact(Transaction $transaction): void
    {

        $account = $transaction->account;

        $date = Carbon::parse($transaction->created_at);
        $status = $transaction->status == TransactionStatusEnum::PAID ? InstallmentStatusEnum::PAID : InstallmentStatusEnum::PENDING;
        Installment::create([
            'number'         => 1,
            'amount'         => $transaction->amount,
            'due_date'       => $date,
            'status'         => $status,
            'transaction_id' => $transaction->id,
            'family_id'      => $transaction->familyUser->family_id,
            'account_id'     => $account->id
        ]);

        // Só impacta saldo se estiver Pago ou Compensado
        $validStatuses = [
            TransactionStatusEnum::PAID,
            TransactionStatusEnum::CLEARED
        ];

        if (!in_array($transaction->status, $validStatuses)) {
            return;
        }

        match ($transaction->type) {
            TransactionTypeEnum::EXPENSE => $account->decrement('balance', $transaction->amount),
            TransactionTypeEnum::INCOME => $account->increment('balance', $transaction->amount),
            TransactionTypeEnum::TRANSFER => $this->handleTransfer($transaction, $account),
        };
    }

    private function handleTransfer(Transaction $transaction, Account $sourceAccount): void
    {


        if ($transaction->destination_account_id) {
            $sourceAccount->decrement('balance', $transaction->amount);
            Account::find($transaction->destination_account_id)->increment('balance', $transaction->amount);
        }
    }

    /**
     * Gera parcelas e vincula a faturas.
     */
    private function processInstallments(Transaction $transaction, ?string $invoiceDate = null): void
    {

        $installmentAmount = (int)($transaction->amount / $transaction->installment_number);

        $familyId = $transaction->familyUser->family_id;

        $card = $transaction->creditCard;

        if ($card) {
            // Ao comprar, o limite USADO aumenta
            $card->increment('used', $transaction->amount);
        }

        $startDate = $invoiceDate ? Carbon::parse($invoiceDate) : Carbon::parse($transaction->created_at);
        for ($i = 1; $i <= $transaction->installment_number; $i++) {
            $dueDate = $startDate->copy()->addMonths($i - 1)->startOfMonth();

            // Se for cartão, ajustamos para o dia de vencimento do cartão
            if ($transaction->credit_card_id) {
                $dueDate->day($transaction->creditCard->due_day);
            }

            $invoiceId = null;
            if ($transaction->credit_card_id) {
                $invoice = $this->getOrCreateInvoice($transaction, $dueDate);
                $invoice->increment('total_amount', $installmentAmount);
            }

            Installment::create([
                'number'         => $i,
                'amount'         => $installmentAmount,
                'due_date'       => $dueDate,
                'status'         => InstallmentStatusEnum::POSTED,
                'transaction_id' => $transaction->id,
                'family_id'      => $familyId,
                'invoice_id'     => $invoice->id,
            ]);

            $invoice->refresh();
        }
    }

    /**
     * Gerencia a Fatura do Cartão.
     */
    private function getOrCreateInvoice(Transaction $transaction, Carbon $date): Invoice
    {
        return app(InvoicesService::class)->getInvoiceByDate($transaction->creditCard, $date);
    }
}
