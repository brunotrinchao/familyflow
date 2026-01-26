<?php

namespace Service;

use App\Enums\InvoiceStatusEnum;
use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Brand;
use App\Models\CreditCard;
use App\Models\FamilyUser;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected PaymentService $service;
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentService::class);
    }

    public function test_pays_an_invoice_fully()
    {
        $amountValue = 2000;
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
            'total_amount'   => $amountValue,
            'status'         => \App\Enums\InvoiceStatusEnum::PENDING,
            'family_id'      => $familyUser->family_id,
            'credit_card_id' => $card->id
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
        ]);

        Installment::factory()->create([
            'family_id'      => $familyUser->family_id,
            'transaction_id' => $transaction->id,
            'invoice_id'     => $invoice->id, // Opcional, dependendo se é cartão ou conta
            'account_id'     => null,
            'number'         => 1,
            'amount'         => $amountValue,
            'due_date'       => now()->startOfMonth()->format('Y-m-d'),
            'status'         => InstallmentStatusEnum::PENDING,
        ]);

        $this->service->payInvoice($invoice, $account, $amountValue);

        $this->assertEquals(0, $account->fresh()->balance);
        $this->assertEquals(\App\Enums\InvoiceStatusEnum::PAID, $invoice->fresh()->status);
    }

}
