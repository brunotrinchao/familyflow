<?php

namespace Database\Seeders;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\FamilyUser;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create('pt_BR');

        // 1. Contexto Base
        $familyUser = FamilyUser::first();
        if (!$familyUser) return;

        $familyId = $familyUser->family_id;
        $accounts = Account::all();
        $cards = CreditCard::all();

        $categoriesExpense = Category::where('type', 'expense')->get();
        $categoriesIncome = Category::where('type', 'income')->get();

        // --- GERAR DADOS PARA OS ÚLTIMOS 4 MESES ---
        for ($m = 3; $m >= 0; $m--) {
            $baseDate = Carbon::now()->subMonths($m);

            // 1. RECEITAS (Income via Account)
            $incomeCount = rand(2, 3);
            for ($i = 0; $i < $incomeCount; $i++) {
                $isSalary = ($i === 0);
                $amount = $isSalary ? $faker->numberBetween(450000, 650000) : $faker->numberBetween(10000, 80000);
                $account = $accounts->random();
                $dueDate = $baseDate->copy()->startOfMonth()->addDays($isSalary ? 4 : rand(1, 28));
                $transaction = Transaction::create([
                    'title'          => $isSalary ? 'Salário Mensal' : 'Transferência Recebida (Pix)',
                    'amount'         => $amount,
                    'type'           => TransactionTypeEnum::INCOME,
                    'source'         => TransactionSourceEnum::ACCOUNT,
                    'status'         => TransactionStatusEnum::PAID,
                    'account_id'     => $account->id,
                    'category_id'    => $categoriesIncome->random()->id,
                    'family_user_id' => $familyUser->id,
                    'date'           => $dueDate,
                ]);

                Installment::create([
                    'transaction_id' => $transaction->id,
                    'number'         => $i,
                    'amount'         => $amount,
                    'due_date'       => $dueDate,
                    'status'         => InstallmentStatusEnum::PAID,
                    'family_id'      => $familyId,
                    'account_id'        => $account->id
                ]);
                $account->increment('balance', $amount);
            }

            // 2. TRANSFERÊNCIAS ENTRE CONTAS (Transfer)
            // Simula mover dinheiro para a reserva ou investimentos
            if ($accounts->count() >= 2) {
                $fromAccount = $accounts->first(); // Geralmente a principal
                $toAccount = $accounts->last();    // Geralmente a reserva
                $transferAmount = $faker->numberBetween(50000, 150000);
                $dueDate = $baseDate->copy()->setDay(rand(5, 10));
                $transaction = Transaction::create([
                    'title'                  => 'Aplicação em Reserva',
                    'amount'                 => $transferAmount,
                    'type'                   => TransactionTypeEnum::TRANSFER,
                    'source'                 => TransactionSourceEnum::ACCOUNT,
                    'status'                 => TransactionStatusEnum::PAID,
                    'account_id'             => $fromAccount->id,
                    'destination_account_id' => $toAccount->id,
                    'category_id'            => $categoriesExpense->random()->id,
                    // Categoria neutra
                    'family_user_id'         => $familyUser->id,
                    'date'                   => $dueDate,
                ]);
                Installment::create([
                    'transaction_id' => $transaction->id,
                    'number'         => $i,
                    'amount'         => -$transferAmount,
                    'due_date'       => $dueDate,
                    'status'         => InstallmentStatusEnum::PAID,
                    'family_id'      => $familyId,
                    'account_id'     => $fromAccount->id
                ]);

                Installment::create([
                    'transaction_id' => $transaction->id,
                    'number'         => $i,
                    'amount'         => $transferAmount,
                    'due_date'       => $dueDate,
                    'status'         => InstallmentStatusEnum::PAID,
                    'family_id'      => $familyId,
                    'account_id'     => $toAccount->id
                ]);

                $fromAccount->decrement('balance', $transferAmount);
                $toAccount->increment('balance', $transferAmount);
            }

            // 3. DESPESAS À VISTA (Expense via Account)
            // Simula Pix, Débito, Contas de Consumo
            for ($i = 0; $i < 8; $i++) {
                $amount = $faker->numberBetween(2000, 45000);
                $account = $accounts->random();
                $dueDate = $baseDate->copy()->setDay(rand(1, 28));
                $transaction = Transaction::create([
                    'title'          => $faker->randomElement([
                        'Mercado',
                        'Farmácia',
                        'Combustível',
                        'Aluguel',
                        'Internet'
                    ]),
                    'amount'         => $amount,
                    'type'           => TransactionTypeEnum::EXPENSE,
                    'source'         => TransactionSourceEnum::ACCOUNT,
                    'status'         => TransactionStatusEnum::PAID,
                    'account_id'     => $account->id,
                    'category_id'    => $categoriesExpense->random()->id,
                    'family_user_id' => $familyUser->id,
                    'date'           => $dueDate,
                ]);

                Installment::create([
                    'transaction_id' => $transaction->id,
                    'number'         => $i,
                    'amount'         => -$amount,
                    'due_date'       => $dueDate,
                    'status'         => InstallmentStatusEnum::PAID,
                    'family_id'      => $familyId,
                    'account_id'     => $account->id
                ]);

                $account->decrement('balance', $amount);
            }

            // 1. Simular 2 Entradas (Salário e Freelance)
            foreach ([
                'Salário Mensal',
                'Freelance'
            ] as $title) {
                $amount = $faker->numberBetween(200000, 500000);
                $account = $accounts->random();
                $dueDate = $baseDate->copy()->startOfMonth()->addDays(rand(0, 5));
                $transaction = Transaction::create([
                    'title'          => $title,
                    'amount'         => $amount,
                    'type'           => TransactionTypeEnum::INCOME,
                    'source'         => TransactionSourceEnum::ACCOUNT,
                    'status'         => TransactionStatusEnum::PAID,
                    'account_id'     => $account->id,
                    'category_id'    => $categoriesIncome->random()->id,
                    'family_user_id' => $familyUser->id,
                    'date'           => $dueDate,
                ]);

                Installment::create([
                    'transaction_id' => $transaction->id,
                    'number'         => $i,
                    'amount'         => $amount,
                    'due_date'       => $dueDate,
                    'status'         => InstallmentStatusEnum::PAID,
                    'family_id'      => $familyId,
                    'account_id'        => $account->id
                ]);
                $account->increment('balance', $amount);
            }

            // 2. Simular 10 Despesas Variadas (À vista na conta)
            for ($i = 0; $i < 10; $i++) {
                $amount = $faker->numberBetween(1000, 30000);
                $account = $accounts->random();

                $dueDate = $baseDate->copy()->setDay(rand(1, 28));
                $transaction = Transaction::create([
                    'title'          => $faker->sentence(2),
                    'amount'         => $amount,
                    'type'           => TransactionTypeEnum::EXPENSE,
                    'source'         => TransactionSourceEnum::ACCOUNT,
                    'status'         => TransactionStatusEnum::PAID,
                    'account_id'     => $account->id,
                    'category_id'    => $categoriesExpense->random()->id,
                    'family_user_id' => $familyUser->id,
                    'date'           => $dueDate,
                ]);

                Installment::create([
                    'transaction_id' => $transaction->id,
                    'number'         => $i,
                    'amount'         => $amount,
                    'due_date'       => $dueDate,
                    'status'         => InstallmentStatusEnum::PAID,
                    'family_id'      => $familyId,
                    'account_id'        => $account->id
                ]);

                $account->decrement('balance', $amount);
            }

            // 3. Simular 5 Compras no Cartão de Crédito (Algumas parceladas)
            foreach ($cards as $card) {
                for ($c = 0; $c < 3; $c++) {
                    $installmentsCount = $faker->randomElement([
                        1,
                        1,
                        1,
                        3,
                        6,
                        12
                    ]); // Mais chances de ser 1x
                    $totalAmount = $faker->numberBetween(5000, 150000);
                    $installmentValue = (int)($totalAmount / $installmentsCount);
                    $purchaseDate = $baseDate->copy()->setDay(rand(1, 28));

                    $transaction = Transaction::create([
                        'title'              => $faker->sentence(2),
                        'amount'             => $totalAmount,
                        'type'               => TransactionTypeEnum::EXPENSE,
                        'source'             => TransactionSourceEnum::CREDIT_CARD,
                        'status'             => TransactionStatusEnum::POSTED,
                        'credit_card_id'     => $card->id,
                        'category_id'        => $categoriesExpense->random()->id,
                        'installment_number' => $installmentsCount,
                        'family_user_id'     => $familyUser->id,
                        'date'               => $purchaseDate,
                    ]);

                    for ($i = 1; $i <= $installmentsCount; $i++) {
                        $dueDate = $purchaseDate->copy()->addMonths($i - 1)->setDay($card->due_day);
                        $periodDate = $dueDate->copy()->startOfMonth();

                        // Lógica de Fatura Fechada/Aberta
                        $closingDate = $periodDate->copy()->day((int)$card->closing_day);
                        $isClosed = $closingDate->lessThanOrEqualTo(Carbon::now());

                        $invoice = Invoice::firstOrCreate([
                            'credit_card_id' => $card->id,
                            'period_date'    => $periodDate,
                            'family_id'      => $familyId,
                        ], [
                            'status' => $isClosed ? InvoiceStatusEnum::CLOSED : InvoiceStatusEnum::OPEN,
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

                        $invoice->increment('total_amount', $installmentValue);
                    }
                }
            }
        }
    }
}
