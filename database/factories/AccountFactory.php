<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Brand;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 🚨 Obtém uma Brand do tipo BANK
        $bankBrand = Brand::factory()->bank()->create();

        return [
            'name'           => $this->faker->unique()->randomElement([
                'Conta Principal',
                'Conta de Investimento',
                'Conta Salário',
                'Conta Poupança'
            ]),
            'balance'        => $this->faker->numberBetween(1000, 1000000),
            'family_user_id' => FamilyUser::factory(),
        ];
    }
}
