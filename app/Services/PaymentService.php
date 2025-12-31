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
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
    public function payInvoice(Invoice $invoice, Account $account, int $amount): Payment
    {
        return DB::transaction(function () use ($invoice, $account, $amount) {
            $payment = Payment::create([
                'amount'     => $amount,
                'paid_at'    => now(),
                'invoice_id' => $invoice->id,
            ]);

            $account->decrement('balance', $amount);

            // Se o pagamento cobrir a fatura, atualiza status via Enum
            if ($amount >= $invoice->total_amount) {
                $invoice->update(['status' => InvoiceStatusEnum::PAID]);
                $invoice->installments()->update(['status' => InstallmentStatusEnum::PAID]);
            } else {
                $invoice->update(['status' => InvoiceStatusEnum::PARTIAL]);
            }

            return $payment;
        });
    }

    public function payTransaction(Transaction $transaction, Account $account): Payment
    {
        return DB::transaction(function () use ($transaction, $account) {
            $payment = Payment::create([
                'amount'         => $transaction->amount,
                'paid_at'        => now(),
                'transaction_id' => $transaction->id,
            ]);

            $account->decrement('balance', $transaction->amount);
            $transaction->update(['status' => TransactionStatusEnum::PAID]);

            return $payment;
        });
    }
}
