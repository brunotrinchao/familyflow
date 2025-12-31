<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Account;
use App\Models\Brand;
use App\Models\CreditCard;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditCard>
 */
class CreditCardFactory extends Factory
{

    protected $model = CreditCard::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cardBrand = Brand::factory()->creditCardBrand()->create();

        $limit = $this->faker->numberBetween(50000, 5000000); // R$ 500 a R$ 50.000 de limite

        return [
            // 🚨 Relacionamentos:
            'family_user_id' => FamilyUser::factory(),
            'brand_id' => $cardBrand->id, // Usa a Brand do tipo CREDITCARD
            'account_id' => Account::factory(), // Conta de onde o pagamento será debitado

            // Dados de Identificação
            'name' => $this->faker->randomElement(['Principal', 'Secundário', 'Viagem']) . ' ' . $cardBrand->name,
            'last_four_digits' => $this->faker->numerify('####'),

            // Ciclo de Faturamento
            'closing_day' => $this->faker->numberBetween(1, 28),
            'due_day' => $this->faker->numberBetween(1, 28),

            // Limites e Status
            'limit' => $limit,
            // Usa até 30% do limite
            'used' => 0,
            'status' => StatusEnum::ACTIVE,
        ];
    }
}
