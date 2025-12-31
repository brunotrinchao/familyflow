<?php

namespace Database\Factories;

use App\Enums\StatusEnum;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['BANK', 'CREDITCARD']);

        // Define nomes mais apropriados baseados no tipo
        if ($type === 'BANK') {
            $name = $this->faker->unique()->randomElement(['Nubank', 'Itaú', 'Bradesco', 'Santander', 'Banco do Brasil']) . ' ' . $this->faker->bank;
        } else {
            $name = $this->faker->unique()->randomElement(['Visa', 'Mastercard', 'Elo', 'Amex', 'Hipercard']);
        }

        return [
            'name' => $name,
            'type' => $type,
            'icon_path' => $this->faker->imageUrl(64, 64, 'abstract'), // Exemplo de path
            'status' => $this->faker->randomElement(StatusEnum::cases()),
        ];
    }

    /**
     * Define o estado da marca como BANCO.
     */
    public function bank(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'BANK',
                'name' => $this->faker->unique()->randomElement(['Nubank', 'Itaú', 'Bradesco', 'Santander']) . ' S.A.',
            ];
        });
    }

    /**
     * Define o estado da marca como BANDEIRA DE CARTÃO.
     */
    public function creditCardBrand(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'CREDITCARD',
                'name' => $this->faker->unique()->randomElement(['Visa', 'Mastercard', 'Elo', 'Amex']),
            ];
        });
    }
}
