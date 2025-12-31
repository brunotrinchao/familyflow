<?php

namespace Service;

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\FamilyUser;
use App\Models\Installment;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);
    }

    public function test_increases_account_balance_on_income_transaction()
    {
        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();

        $account = Account::factory()->create([
            'balance'        => 1000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        $category = Category::factory()->create(['type' => TransactionTypeEnum::INCOME]);

        $this->service->create([
            'title'          => 'Salário',
            'amount'         => 5000,
            'type'           => TransactionTypeEnum::INCOME,
            'source'         => TransactionSourceEnum::ACCOUNT,
            'status'         => TransactionStatusEnum::PAID,
            'account_id'     => $account->id,
            'category_id'    => $category->id,
            'family_user_id' => $account->family_user_id,
            'date'           => Carbon::now()->format('Y-m-d'),
        ]);

        $this->assertEquals(6000, $account->fresh()->balance);
    }

    public function test_decreases_account_balance_on_expense_transaction()
    {
        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();

        $account = Account::factory()->create([
            'balance'        => 5000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        $category = Category::factory()->create(['type' => TransactionTypeEnum::EXPENSE]);

        $this->service->create([
            'title'          => 'Mercado',
            'amount'         => 1200,
            'type'           => TransactionTypeEnum::EXPENSE,
            'source'         => TransactionSourceEnum::ACCOUNT,
            'status'         => TransactionStatusEnum::PAID,
            'account_id'     => $account->id,
            'category_id'    => $category->id,
            'family_user_id' => $account->family_user_id,
            'date'           => Carbon::now()->format('Y-m-d'),
        ]);

        $this->assertEquals(3800, $account->fresh()->balance);
    }

    public function test_processes_transfer_between_two_accounts()
    {
        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();

        $from = Account::factory()->create([
            'balance'        => 2000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        $to = Account::factory()->create([
            'balance'        => 500,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        $category = Category::factory()->create();

        $this->service->create([
            'title' => 'Reserva',
            'amount' => 1000,
            'type' => TransactionTypeEnum::TRANSFER,
            'source' => TransactionSourceEnum::ACCOUNT,
            'status' => TransactionStatusEnum::PAID,
            'account_id' => $from->id,
            'destination_account_id' => $to->id,
            'category_id' => $category->id,
            'family_user_id' => $from->family_user_id,
            'date'           => Carbon::now()->format('Y-m-d'),
        ]);

        $this->assertEquals(1000, $from->fresh()->balance);
        $this->assertEquals(1500, $to->fresh()->balance);
    }

    public function test_creates_installments_and_invoices_for_credit_card_transactions()
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
            'account_id' => $account->id,
        ]);
        $category = Category::factory()->create(['type' => TransactionTypeEnum::EXPENSE]);

        $transaction = $this->service->create([
            'title' => 'Notebook',
            'amount' => 3000,
            'type' => TransactionTypeEnum::EXPENSE,
            'source' => TransactionSourceEnum::CREDIT_CARD,
            'status' => TransactionStatusEnum::POSTED,
            'credit_card_id' => $card->id,
            'category_id' => $category->id,
            'installment_number' => 3,
            'family_user_id' => $card->family_user_id,
            'created_at' => now(),
            'date'           => Carbon::now()->format('Y-m-d'),
        ]);

        $this->assertCount(3, Installment::all());
        $this->assertEquals(1000, Installment::first()->amount);

        // Verifica se criou ao menos uma fatura (Invoice)
        $this->assertDatabaseHas('invoices', [
            'credit_card_id' => $card->id,
        ]);
    }
}
