<?php

namespace Database\Factories;

use App\Enums\CategoryColorPaletteEnum;
use App\Enums\CategoryIconEnum;
use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $randomCase = CategoryIconEnum::randomValue();
        $colorRandom = CategoryColorPaletteEnum::getRandomColorHex();

        $icon = CategoryIconEnum::from($randomCase);

        return [
            'name' => fake()->name,
            'icon' => CategoryIconEnum::Bonus,
            'type' => fake()->randomElement(array_column(TransactionTypeEnum::cases(), 'value')),
            'color' => $colorRandom,
        ];
    }
}
