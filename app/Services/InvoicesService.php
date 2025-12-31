<?php

namespace App\Services;

use App\Enums\CategoryIconEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\StatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class InvoicesService
{
    /**
     * Recalcula o valor total da fatura somando todas as parcelas vinculadas.
     */
    public function recalculateTotal(Invoice $invoice): void
    {
        $total = $invoice->installments()->sum('amount');
        $invoice->update(['total_amount' => $total]);
    }

    /**
     * Fecha a fatura (muda de Aberta para Fechada/Pendente).
     * Geralmente executado no 'closing_day' do cartão.
     */
    public function closeInvoice(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatusEnum::OPEN) {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatusEnum::PENDING]);

            // Atualiza as parcelas que estavam "Aguardando" para "Lançadas na Fatura"
            $invoice->installments()
                ->where('status', InstallmentStatusEnum::PENDING)
                ->update(['status' => InstallmentStatusEnum::POSTED]);
        });
    }

    /**
     * Busca a fatura ativa para um cartão em uma data específica.
     */
    public function getInvoiceByDate(CreditCard $card, Carbon $date): Invoice
    {
        return Invoice::firstOrCreate(
            [
                'credit_card_id' => $card->id,
                'period_date'    => $date->copy()->startOfMonth(),
                'family_id'      => $card->familyUser->family_id,
            ],
            [
                'total_amount' => 0,
                'status'       => InvoiceStatusEnum::OPEN,
            ]);
    }

    /**
     * Consolida todas as faturas abertas de uma família que já passaram da data de fechamento.
     */
    public function closeAllExpiredInvoices(int $familyId): void
    {
        $today = now();

        $invoices = Invoice::where('family_id', $familyId)
            ->where('status', InvoiceStatusEnum::OPEN)
            ->whereHas('creditCard', function ($query) use ($today) {
                $query->where('closing_day', '<=', $today->day);
            })
            ->get();

        foreach ($invoices as $invoice) {
            $this->closeInvoice($invoice);
        }
    }


    /**
     * Confirma o pagamento total de uma fatura.
     * * @param Invoice $invoice A fatura a ser paga
     * @param Account $account A conta de onde sairá o dinheiro
     * @param int|null $amount Valor pago (opcional, assume total_amount se nulo)
     */
    public function confirmPayment(Invoice $invoice, Account $account, ?int $amount = null): Payment
    {
        $totalInvoiceAmount = $invoice->total_amount;
        $amountPaid = $amount ?? $totalInvoiceAmount;

        return DB::transaction(function () use ($invoice, $account, $amountPaid, $totalInvoiceAmount) {

            // 1. Registrar o Pagamento (Auditoria)
            $payment = Payment::create([
                'amount'         => $amountPaid,
                'paid_at'        => now(),
                'invoice_id'     => $invoice->id,
                'transaction_id' => $invoice->installments?->first()->transaction_id,
                'status',
            ]);

            // 2. Movimentação Financeira
            $account->decrement('balance', $amountPaid);
            $invoice->creditCard->decrement('used', $amountPaid);

            // 3. Verificação de Saldo Remanescente (Rolagem de Dívida)
            if ($amountPaid < $totalInvoiceAmount) {
                $remainingBalance = $totalInvoiceAmount - $amountPaid;
                $this->rollOverRemainingBalance($invoice, $remainingBalance);

                $invoice->update(['status' => InvoiceStatusEnum::PARTIAL]);
            } else {
                $invoice->update(['status' => InvoiceStatusEnum::PAID]);
            }


            // 4. Atualizar status das parcelas da fatura atual
            // Nota: Mesmo em pagamento parcial, as parcelas saem da fatura atual
            // e o que sobra vira um novo débito na próxima.
            $invoice->installments()->update(['status' => InstallmentStatusEnum::PAID]);

            return $payment;
        });
    }

    /**
     * Cancela todos os pagamentos vinculados à fatura e restaura os status.
     */
    public function cancelPayments(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            // 1. Recupera os pagamentos vinculados
            $payments = $invoice->payments;

            foreach ($payments as $payment) {
                // Estorna o saldo para a conta que originou o pagamento
                if ($payment->account) {
                    $payment->account->increment('balance', $payment->amount);
                }

                // Deleta o registro do pagamento (ou marca como cancelado)
                $payment->delete();
            }

            $invoice->creditCard->increment('used', $invoice->total_amount);

            // 2. Altera o status da Invoice para PENDING (Aguardando novo pagamento)
            $invoice->update([
                'status' => InvoiceStatusEnum::PENDING
            ]);

            // 3. Altera o status de todas as parcelas (Installments) de volta para POSTED (Na fatura)
            $invoice->installments()->update([
                'status' => InstallmentStatusEnum::POSTED
            ]);
        });
    }

    /**
     * Move o saldo não pago para a fatura do próximo mês.
     */
    private function rollOverRemainingBalance(Invoice $currentInvoice, int $amount): void
    {
        $nextMonthDate = $currentInvoice->period_date->copy()->addMonth();

        // Localiza ou cria a fatura do próximo mês
        $nextInvoice = $this->getInvoiceByDate($currentInvoice->creditCard, $nextMonthDate);

        // Incrementa o valor da próxima fatura
        $nextInvoice->increment('total_amount', $amount);

        // Opcional: Criar uma descrição ou log na próxima fatura
        // Ex: "Saldo devedor fatura anterior"
    }

    public function createInvoiceWithImport(array $data): ?Invoice
    {
        $familyUserId = Auth::user()->families()->first()->id;

        //        Criar / obter Conta
        $account = Account::firstOrCreate(
            [
                'name'     => $data['bank_name'],
                'brand_id' => $data['bank_brand'],
            ],
            [
                'balance'        => 0,
                'family_user_id' => $familyUserId,
            ]
        );

        //        Criar obter Cartão
        $creditCard = CreditCard::firstOrCreate(
            [
                'name'     => $data['card_name'],
                'brand_id' => $data['card_brand'],
            ],
            [
                'last_four_digits' => $data['card_last_four'],
                'closing_day'      => $data['closing_day'],
                'due_day'          => $data['due_day'],
                'limit'            => $data['card_limit'],
                'used'             => $data['card_used'],
                'status'           => StatusEnum::ACTIVE,
                'account_id'       => $account->id,
                'family_user_id'   => $familyUserId,
            ]
        );

        //        Cria Transaction
        $transactionService = app(TransactionService::class);

        $defaultCategoryId = Category::where('icon', 'shopping-bag')->first()?->id;

        $today = Carbon::now();

        // 1. Criamos a instância da data da fatura
        $invoiceDate = Carbon::parse($data['invoice']);

        // 2. Alteramos o dia para o dia de fechamento (garantindo que seja um INTEIRO)
        $invoiceDate->day((int)$data['closing_day']);

        // 3. Agora comparamos os objetos ou timestamps
        $invoiceClosed = $invoiceDate->lessThanOrEqualTo($today);

        $invoice = null;
        $data['items']->each(function ($item) use (
            $creditCard,
            $transactionService,
            $data,
            $defaultCategoryId,
            $invoiceClosed,
            &$invoice
        ) {

            $amount = $item['amount'];
            $description = "Fatura importada";
            $parcelasRestantes = 1;

            // CORREÇÃO: A lógica de parcelas deve ser para itens parcelados
            if ($item['is_parcelado']) {
                $parcelasRestantes = ($item['parcela_total'] - $item['parcela_atual']) + 1;
                $amount = $amount * $parcelasRestantes;

                $date = Carbon::parse($item['date'])->format('d/m/Y');
                $description = "Fatura importada:\nTotal de parcelas: {$item['parcela_total']}\nParcela atual: {$item['parcela_atual']}\nData original: {$date}";
            }


            $dataTransaction = [
                'type'               => TransactionTypeEnum::EXPENSE,
                'amount'             => $amount,
                'category_id'        => $defaultCategoryId,
                'family_user_id'     => $creditCard->family_user_id,
                'credit_card_id'     => $creditCard->id,
                'source'             => TransactionSourceEnum::CREDIT_CARD,
                'date'               => Carbon::parse($item['date']),
                'title'              => $item['description'],
                'description'        => $description,
                'status'             => TransactionStatusEnum::POSTED,
                'installment_number' => $parcelasRestantes,
                'invoice'            => Carbon::parse($data['invoice'])->startOfMonth()->format('Y-m-d'),
            ];

            $transaction = $transactionService->create($dataTransaction);
            if ($transaction->installments()->exists()) {
                $invoice = $transaction->installments()->first()->invoice;
                if ($invoiceClosed) {
                    $invoice->update(['status' => InvoiceStatusEnum::CLOSED]);
                }
                $invoice->load('installments');
            }
        });
        //        Criar invoice

        return $invoice;
        //        Criar parcelas

    }
}
