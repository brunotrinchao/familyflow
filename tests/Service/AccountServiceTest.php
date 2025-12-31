<?php

namespace Service;

use App\Enums\InvoiceStatusEnum;
use App\Models\Account;
use App\Models\Brand;
use App\Models\FamilyUser;
use App\Models\Invoice;
use App\Services\AccountService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AccountService::class);
    }

    public function test_can_update_account_details()
    {

        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();
        // Arrange
        $account = Account::factory()->create([
            'name' => 'Nome Antigo',
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);
        $newBrand = Brand::factory()->create();

        $updateData = [
            'name' => 'Novo Nome da Conta',
            'brand_id' => $newBrand->id,
        ];

        // Act
        $updatedAccount = $this->service->update($account, $updateData);

        // Assert
        $this->assertEquals('Novo Nome da Conta', $updatedAccount->name);
        $this->assertEquals($newBrand->id, $updatedAccount->brand_id);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Novo Nome da Conta'
        ]);
    }

    public function test_can_create_account_with_initial_balance()
    {
        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();

        $data = [
            'name'           => 'Banco do Brasil',
            'balance'        => 150000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
            // Agora garantimos que não é nulo
        ];

        $account = $this->service->create($data);

        $this->assertEquals(150000, $account->balance);
    }

    public function test_can_adjust_account_balance_manually()
    {
        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();
        // Arrange
        $account = Account::factory()->create([
            'balance' => 100000,
            'brand_id'       => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        // Act
        $this->service->adjustBalance($account, 50000); // Ajustando para R$ 500,00

        // Assert
        $this->assertEquals(50000, $account->fresh()->balance);
    }

    public function test_should_throw_an_error_when_creating_without_required_fields()
    {
        // Este teste garante que as regras de integridade do banco (que causaram seu erro anterior)
        // são respeitadas ou capturadas pela lógica de validação.
        $this->expectException(\Throwable::class);

        $this->service->create([
            'name' => 'Conta Inválida',
            // 'family_user_id' e 'brand_id' ausentes para forçar erro de integridade
        ]);
    }

    public function test_verifies_account_belongs_to_correct_family_user()
    {
        // Arrange
        $familyUser = FamilyUser::factory()->create();
        $brand = Brand::factory()->create();

        // Act
        $account = $this->service->create([
            'name' => 'Conta de Teste',
            'balance' => 0,
            'brand_id' => $brand->id,
            'family_user_id' => $familyUser->id,
        ]);

        // Assert
        $this->assertEquals($familyUser->id, $account->family_user_id);
        $this->assertTrue($account->familyUser->is($familyUser));
    }
}
