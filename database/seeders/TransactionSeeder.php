<?php

namespace Database\Seeders;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\FamilyUser;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Recuperar Contexto Base
        $familyUser = FamilyUser::first();
        if (!$familyUser) return;

        $familyId = $familyUser->family_id;
        $itau = Account::where('name', 'like', '%Itaú%')->first();
        $nubankAccount = Account::where('name', 'like', '%Nubank%')->first();
        $cardNubank = CreditCard::where('name', 'like', '%Nubank%')->first();

        $catFood = Category::where('name', 'like', '%Alimentação%')->first()
           ?? Category::where('type', 'expense')->first();
        $catSalary = Category::where('name', 'like', '%Salário%')->first()
             ?? Category::where('type', 'income')->first();

        // --- 1. RECEITA (INCOME) À VISTA ---
        $amountIncome = -500000;
        Transaction::create([
            'title'          => 'Salário Mensal',
            'amount'         => $amountIncome,
            'type'           => 'income',
            'source'         => 'account',
            'status'         => 'Paid',
            'account_id'     => $itau->id,
            'category_id'    => $catSalary->id,
            'family_user_id' => $familyUser->id,
            'date'           => Carbon::now()->format('Y-m-d'),
        ]);
        $itau->increment('balance', $amountIncome);

        // --- 2. TRANSFERÊNCIA ENTRE CONTAS ---
        $transferAmount = -100000;
        Transaction::create([
            'title'                  => 'Transferência para Reserva',
            'amount'                 => $transferAmount,
            'type'                   => 'transfer',
            'source'                 => 'account',
            'status'                 => 'Paid',
            'account_id'             => $itau->id,
            'destination_account_id' => $nubankAccount->id,
            'category_id'            => $catFood->id,
            'family_user_id'         => $familyUser->id,
            'date'                   => Carbon::now()->format('Y-m-d'),
        ]);
        $itau->decrement('balance', $transferAmount);
        $nubankAccount->increment('balance', $transferAmount);

        // --- 3. DESPESA PARCELADA NO CARTÃO (6x) ---
        $totalAmount = -240000;
        $installmentsCount = 6;
        $installmentValue = (int)($totalAmount / $installmentsCount);

        $transaction = Transaction::create([
            'title'              => 'Smartphone Novo',
            'amount'             => $totalAmount,
            'type'               => 'expense',
            'source'             => 'credit_card',
            'status'             => 'Posted',
            'credit_card_id'     => $cardNubank->id,
            'category_id'        => $catFood->id,
            'installment_number' => $installmentsCount,
            'family_user_id'     => $familyUser->id,
            'date'               => Carbon::now()->format('Y-m-d'),
        ]);

        // Criando as parcelas e faturas manualmente
        for ($i = 1; $i <= $installmentsCount; $i++) {
            $dueDate = Carbon::now()->addMonths($i - 1)->setDay($cardNubank->due_day);
            $periodDate = $dueDate->copy()->startOfMonth();

        $today = Carbon::now();
        $invoiceDate =$periodDate;
        $invoiceDate->day((int)$cardNubank->closing_day);

        // 3. Agora comparamos os objetos ou timestamps
        $invoiceClosed = $invoiceDate->lessThanOrEqualTo($today);

            // Busca ou cria a fatura para aquele mês
            $invoice = Invoice::firstOrCreate([
                'credit_card_id' => $cardNubank->id,
                'period_date'    => $periodDate,
                'family_id'      => $familyId,
                'status'         => $invoiceClosed ? InvoiceStatusEnum::CLOSED : InvoiceStatusEnum::OPEN,
            ]);

            Installment::create([
                'transaction_id' => $transaction->id,
                'number'         => $i,
                'amount'         => $installmentValue,
                'due_date'       => $dueDate,
                'status'         => InstallmentStatusEnum::POSTED,
                'family_id'      => $familyId,
                'invoice_id'     => $invoice->id,
            ]);

            // Atualiza o total da fatura
            $invoice->increment('total_amount', $installmentValue);
        }
    }
}
