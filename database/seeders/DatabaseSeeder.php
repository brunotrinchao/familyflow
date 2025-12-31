<?php

namespace Database\Seeders;

use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\FamilyStatusEnum;
use App\Enums\ProfileUserEnum;
use App\Enums\RoleUserEnum;
use App\Enums\StatusEnum;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CreditCard;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. FAMILY DEFAULT
        $defaultFamily = Family::factory()->create([
            'name' => 'Family Default',
            'slug' => 'family-default',
        ]);

        // 2. USER SUPER ADMIN
        $superAdminUser = User::factory()->create([
            'name'     => 'Super User',
            'email'    => 'admin@admin.com',
            'password' => bcrypt('admin'),
        ]);

        // 3. VÍNCULO TENANT (O ponto crucial da sua modelagem)
        // Usamos sync para garantir que o relacionamento exista e recuperamos o Pivot ID
        $superAdminUser->families()->sync([$defaultFamily->id => ['role' => RoleUserEnum::ROLE_SUPER_ADMIN]]);

        $familyUser = FamilyUser::where('user_id', $superAdminUser->id)
            ->where('family_id', $defaultFamily->id)
            ->first();

        // 4. CATEGORIAS (Utilizando seus Enums)
        foreach (CategoryIconEnum::getDefault() as $categoryType => $iconList) {
            /** @var CategoryIconEnum $iconCase */
            foreach ($iconList as $iconCase) {

                // Garante que o tipo seja a instância do Enum (ex: CategoryTypeEnum::Expense)
                $categoryTypeEnum = CategoryTypeEnum::tryFrom($categoryType);

                if (!$categoryTypeEnum) {
                    continue;
                }

                Category::factory()->create([
                    'name' => $iconCase->getLabel(),
                    'icon' => $iconCase->value,
                    'type' => $categoryTypeEnum->value,
                ]);
            }
        }

        // 5. MARCAS (Brands)
        $this->call([
            BrandsSeed::class,
        ]);

        // Recupera marcas para vincular abaixo
        $brands = Brand::pluck('id', 'name')->toArray();

        // 6. CONTAS BANCÁRIAS (ACCOUNTS)
        // Note o uso de 'family_user_id' em vez de family_id/user_id separados
        $accountsData = [
            ['name'     => 'Conta Principal Itaú',
             'balance'  => 850000,
             'brand_id' => $brands['Itaú Unibanco']
            ],
            ['name'     => 'Conta Roxo (Nubank)',
             'balance'  => 1200000,
             'brand_id' => $brands['Nubank']
            ],
            ['name'     => 'Conta Investimento Inter',
             'balance'  => 5000000,
             'brand_id' => $brands['Inter']
            ],
            ['name'     => 'Caixa Poupanca',
             'balance'  => 150000,
             'brand_id' => $brands['Caixa Econômica Federal']
            ],
            ['name'     => 'Dinheiro em Mãos',
             'balance'  => 25000,
             'brand_id' => $brands['Nubank']
            ],
        ];

        $createdAccounts = [];
        foreach ($accountsData as $data) {
            $createdAccounts[] = Account::create(array_merge($data, [
                'family_user_id' => $familyUser->id,
            ]));
        }

        // 7. CARTÕES DE CRÉDITO (CREDIT_CARDS)
        $creditCardsData = [
            [
                'name'        => 'Nubank Platinum',
                'brand_id'    => $brands['Mastercard'] ?? $brands['Nubank'],
                'account_id'  => $createdAccounts[1]->id,
                'closing_day' => 8,
                'due_day'     => 15,
                'limit'       => 1500000,
                'used'        => 350000,
            ],
            [
                'name'        => 'Itaú Black',
                'brand_id'    => $brands['Mastercard'] ?? $brands['Itaú Unibanco'],
                'account_id'  => $createdAccounts[0]->id,
                'closing_day' => 3,
                'due_day'     => 10,
                'limit'       => 3000000,
                'used'        => 100000,
            ],
            [
                'name'        => 'Ourocard Visa',
                'brand_id'    => $brands['Visa'] ?? $brands['Caixa Econômica Federal'],
                'account_id'  => $createdAccounts[3]->id,
                'closing_day' => 13,
                'due_day'     => 20,
                'limit'       => 500000,
                'used'        => 50000,
            ],
        ];

        foreach ($creditCardsData as $card) {
            CreditCard::create(array_merge($card, [
                'family_user_id'   => $familyUser->id,
                'last_four_digits' => fake()->numerify('####'),
                'status'           => StatusEnum::ACTIVE->value,
            ]));
        }

        $this->call([
            TransactionSeeder::class,
        ]);
    }
}
