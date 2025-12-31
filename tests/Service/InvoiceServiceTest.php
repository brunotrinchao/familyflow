<?php

namespace Service;

use App\Enums\InstallmentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InvoicesService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected InvoicesService $service;
    private Family $family;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoicesService::class);
    }

    public function test_closes_an_invoice_correctly()
    {
        $familyUser = FamilyUser::factory()->create();

        $brand = Brand::factory()->create();

        $account = Account::factory()->create([
            'balance'        => 2000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        $card = CreditCard::factory()->create([
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
            'account_id'     => $account->id,
        ]);

        /* @var Invoice $invoice */
        $invoice = Invoice::factory()->create([
            'status'         => InvoiceStatusEnum::OPEN,
            'family_id'      => $familyUser->family_id,
            'credit_card_id' => $card->id
        ]);

        $this->service->closeInvoice($invoice);

        $this->assertEquals(\App\Enums\InvoiceStatusEnum::PENDING, $invoice->fresh()->status);
    }

    public function test_updates_all_financial_indicators_on_confirm_payment()
    {
        $crediCardLimit = 50000;
        $amountValue = 200000;
        $limitUsed = $amountValue;

        $familyUser = FamilyUser::factory()->create();

        $brand = Brand::factory()->create();

        $category = Category::factory()->create(['type' => TransactionTypeEnum::EXPENSE]);

        $account = Account::factory()->create([
            'balance'        => 100000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);
        $card = CreditCard::factory()->create([
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
            'account_id'     => $account->id,
            'limit'          => $crediCardLimit,
            'used'           => $limitUsed
        ]);

        /* @var Invoice $invoice */
        $invoice = Invoice::factory()->create([
            'status'         => InvoiceStatusEnum::OPEN,
            'family_id'      => $familyUser->family_id,
            'credit_card_id' => $card->id,
            'period_date'    => now()->startOfMonth(),
            'total_amount'   => $amountValue,
        ]);

        $transaction = Transaction::factory()->create([
            'source'             => TransactionSourceEnum::CREDIT_CARD,
            'type'               => TransactionTypeEnum::EXPENSE,
            'date'               => now()->format('Y-m-d'),
            'amount'             => $amountValue,
            'title'              => 'Compra TESTE 1',
            'description'        => 'Crescrição teste',
            'status'             => TransactionStatusEnum::PENDING,
            'installment_number' => 1,
            'family_user_id'     => $familyUser->id,
            'category_id'        => $category->id
        ]);

        Installment::factory()->create([
            'family_id'      => $familyUser->family_id,
            'transaction_id' => $transaction->id,
            'invoice_id'     => $invoice->id,
            // Opcional, dependendo se é cartão ou conta
            'account_id'     => null,
            'number'         => 1,
            'amount'         => $amountValue,
            'due_date'       => now()->startOfMonth()->format('Y-m-d'),
            'status'         => InstallmentStatusEnum::PENDING,
        ]);

        // Act: Paga apenas R$ 150,00 (Faltam R$ 50,00)
        $this->service->confirmPayment($invoice, $account);

        // Assert
        // 1. A fatura atual deve ser PARCIAL
        $this->assertEquals(InvoiceStatusEnum::PAID, $invoice->fresh()->status);

        // 2. Limite do cartão: 2000 - 2000 = 0 (liberado)
        $this->assertEquals(0, $card->fresh()->used);

        // 3. Status da Fatura
        $this->assertEquals(InvoiceStatusEnum::PAID, $invoice->fresh()->status);

        // 4. Status da Parcela
        $this->assertEquals(InstallmentStatusEnum::PAID, $invoice->installments()->first()->status);

    }

    public function test_installment_confirm_payment()
    {
        $crediCardLimit = 50000;
        $amountValue = 200000;
        $limitUsed = $amountValue;
        $installmentNumber = 3;

        $valueInstallment = intdiv($amountValue, $installmentNumber);
        $remaining = $valueInstallment + ($amountValue - ($valueInstallment * $installmentNumber));

        $familyUser = FamilyUser::factory()->create();

        $brand = Brand::factory()->create();

        $category = Category::factory()->create(['type' => TransactionTypeEnum::EXPENSE]);

        $account = Account::factory()->create([
            'balance'        => 100000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);
        $card = CreditCard::factory()->create([
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
            'account_id'     => $account->id,
            'limit'          => $crediCardLimit,
            'used'           => $limitUsed
        ]);

        $transaction = Transaction::factory()->create([
            'source'             => TransactionSourceEnum::CREDIT_CARD,
            'type'               => TransactionTypeEnum::EXPENSE,
            'date'               => now()->format('Y-m-d'),
            'amount'             => $amountValue,
            'title'              => 'Compra TESTE 1',
            'description'        => 'Descrição teste',
            'status'             => TransactionStatusEnum::PENDING,
            'installment_number' => 1,
            'family_user_id'     => $familyUser->id,
            'category_id'        => $category->id
        ]);


        $invoices = new Collection();
        for ($i = 0; $i < $installmentNumber; $i++) {
            /* @var Invoice $invoice */
            $invoice = Invoice::factory()->create([
                'status'         => InvoiceStatusEnum::OPEN,
                'family_id'      => $familyUser->family_id,
                'credit_card_id' => $card->id,
                'period_date'    => now()->addMonth($i - 1)->startOfMonth(),
                'total_amount'   => $i < $installmentNumber ? $valueInstallment : $remaining,
            ]);
            $invoices->push($invoice);

            Installment::factory()->create([
                'family_id'      => $familyUser->family_id,
                'transaction_id' => $transaction->id,
                'invoice_id'     => $invoice->id,
                'account_id'     => null,
                'number'         => 1,
                'amount'         => $i < $installmentNumber ? $valueInstallment : $remaining,
                'due_date'       => now()->startOfMonth()->format('Y-m-d'),
                'status'         => InstallmentStatusEnum::PENDING,
            ]);
        }
        /* @var Invoice $invoiceFirst */
        $invoiceFirst = $invoices->first();

        $this->service->confirmPayment($invoiceFirst, $account);
        // Assert
        // 1. A fatura atual deve ser PARCIAL
        $this->assertEquals(InvoiceStatusEnum::PAID, $invoiceFirst->fresh()->status);

        // 2. Limite do cartão: 2000 - 2000 = 0 (liberado)
        $this->assertEquals($limitUsed - $valueInstallment, $card->fresh()->used);

        // 3. Status da Fatura
        $this->assertEquals(InvoiceStatusEnum::PAID, $invoiceFirst->fresh()->status);

        // 4. Status da Parcela
        $this->assertEquals(InstallmentStatusEnum::PAID, $invoiceFirst->installments()->first()->status);

    }

    public function test_installment_value_remaning_confirm_payment()
    {
        $crediCardLimit = 50000;
        $amountValue = 200000;
        $limitUsed = $amountValue;
        $installmentNumber = 3;

        $valueInstallment = intdiv($amountValue, $installmentNumber);
        $remaining = $valueInstallment + ($amountValue - ($valueInstallment * $installmentNumber));

        $familyUser = FamilyUser::factory()->create();

        $brand = Brand::factory()->create();

        $category = Category::factory()->create(['type' => TransactionTypeEnum::EXPENSE]);

        $account = Account::factory()->create([
            'balance'        => 100000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);
        $card = CreditCard::factory()->create([
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
            'account_id'     => $account->id,
            'limit'          => $crediCardLimit,
            'used'           => $limitUsed
        ]);

        $transaction = Transaction::factory()->create([
            'source'             => TransactionSourceEnum::CREDIT_CARD,
            'type'               => TransactionTypeEnum::EXPENSE,
            'date'               => now()->format('Y-m-d'),
            'amount'             => $amountValue,
            'title'              => 'Compra TESTE 1',
            'description'        => 'Descrição teste',
            'status'             => TransactionStatusEnum::PENDING,
            'installment_number' => 1,
            'family_user_id'     => $familyUser->id,
            'category_id'        => $category->id
        ]);


        $invoices = new Collection();
        for ($i = 1; $i <= $installmentNumber; $i++) {
            /* @var Invoice $invoice */
            $invoice = Invoice::factory()->create([
                'status'         => InvoiceStatusEnum::OPEN,
                'family_id'      => $familyUser->family_id,
                'credit_card_id' => $card->id,
                'period_date'    => now()->addMonth($i - 1)->startOfMonth(),
                'total_amount'   => $i < $installmentNumber ? $valueInstallment : $remaining,
            ]);
            $invoices->push($invoice);

            Installment::factory()->create([
                'family_id'      => $familyUser->family_id,
                'transaction_id' => $transaction->id,
                'invoice_id'     => $invoice->id,
                'account_id'     => null,
                'number'         => 1,
                'amount'         => $i < $installmentNumber ? $valueInstallment : $remaining,
                'due_date'       => now()->startOfMonth()->format('Y-m-d'),
                'status'         => InstallmentStatusEnum::PENDING,
            ]);
        }
        /* @var Invoice $invoiceFirst */
        $invoiceFirst = $invoices->last();

        $this->service->confirmPayment($invoiceFirst, $account);
        // Assert
        // 1. A fatura atual deve ser PARCIAL
        $this->assertEquals(InvoiceStatusEnum::PAID, $invoiceFirst->fresh()->status);

        // 2. Limite do cartão: 2000 - 2000 = 0 (liberado)
        $this->assertEquals($limitUsed - $remaining, $card->fresh()->used);

        // 3. Status da Fatura
        $this->assertEquals(InvoiceStatusEnum::PAID, $invoiceFirst->fresh()->status);

        // 4. Status da Parcela
        $this->assertEquals(InstallmentStatusEnum::PAID, $invoiceFirst->installments()->first()->status);

    }

    public function test_rolls_over_remaining_balance_to_next_invoice_on_partial_payment()
    {
        $crediCardLimit = 50000;
        $amountValue = $this->faker->numberBetween(1000, 50000);
        $amountPaidPartial = $amountValue - 100;

        $familyUser = FamilyUser::factory()->create();

        $brand = Brand::factory()->create();

        $category = Category::factory()->create(['type' => TransactionTypeEnum::EXPENSE]);

        $account = Account::factory()->create([
            'balance'        => 100000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);
        $card = CreditCard::factory()->create([
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
            'account_id'     => $account->id,
            'limit'          => $crediCardLimit
        ]);

        /* @var Invoice $invoice */
        $invoice = Invoice::factory()->create([
            'status'         => InvoiceStatusEnum::OPEN,
            'family_id'      => $familyUser->family_id,
            'credit_card_id' => $card->id,
            'period_date'    => now()->startOfMonth(),
            'total_amount'   => $amountValue,
        ]);

        $transaction = Transaction::factory()->create([
            'source'             => TransactionSourceEnum::CREDIT_CARD,
            'type'               => TransactionTypeEnum::EXPENSE,
            'date'               => now()->format('Y-m-d'),
            'amount'             => $amountValue,
            'title'              => 'Compra TESTE 1',
            'description'        => 'Descrição teste',
            'status'             => TransactionStatusEnum::PENDING,
            'installment_number' => 1,
            'family_user_id'     => $familyUser->id,
            'category_id'        => $category->id
        ]);

        Installment::factory()->create([
            'family_id'      => $familyUser->family_id,
            'transaction_id' => $transaction->id,
            'invoice_id'     => $invoice->id,
            // Opcional, dependendo se é cartão ou conta
            'account_id'     => null,
            'number'         => 1,
            'amount'         => $amountValue,
            'due_date'       => now()->startOfMonth()->format('Y-m-d'),
            'status'         => InstallmentStatusEnum::PENDING,
        ]);

        // Act: Paga apenas R$ 150,00 (Faltam R$ 50,00)
        $this->service->confirmPayment($invoice, $account, $amountPaidPartial);

        // Assert
        // 1. A fatura atual deve ser PARCIAL
        $this->assertEquals(InvoiceStatusEnum::PARTIAL, $invoice->fresh()->status);

        // 2. Deve existir uma fatura para o mês seguinte com os R$ 50,00
        $nextMonth = now()->startOfMonth()->addMonth();
        $nextInvoice = Invoice::where('credit_card_id', $card->id)
            ->where('period_date', $nextMonth)
            ->first();

        $this->assertNotNull($nextInvoice);
        $this->assertEquals($amountValue - $amountPaidPartial, $nextInvoice->total_amount);

        // 3. O limite usado do cartão deve refletir que R$ 50 ainda estão ocupados
        $this->assertEquals($amountPaidPartial * -1, $card->fresh()->used);
    }
}
