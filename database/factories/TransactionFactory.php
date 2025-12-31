<?php

namespace Database\Factories;

use App\Enums\TransactionSourceEnum;
use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Account;
use App\Models\Category;
use App\Models\Family;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source'      => $this->faker->randomElement(TransactionSourceEnum::cases()),
            'type'        => $this->faker->randomElement(TransactionTypeEnum::cases()),
            'date'        => now()->format('Y-m-d'),
            'amount'      => $this->faker->numberBetween(100, -5000),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->text(100),
            'status'      => TransactionStatusEnum::PENDING,
            'installment_number' => 1,
        ];
    }
}
