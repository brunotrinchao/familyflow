<?php

namespace Database\Factories;

use App\Enums\TransactionStatusEnum;
use App\Models\Account;
use App\Models\Family;
use App\Models\Installment;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Installment>
 */
class InstallmentFactory extends Factory
{
    protected $model = Installment::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id'      => Family::factory(),
            'transaction_id' => Transaction::factory(),
            'invoice_id'     => null, // Opcional, dependendo se é cartão ou conta
            'account_id'     => Account::factory(),
            'number' => 1,
            'amount'   => $this->faker->numberBetween(1000, 50000),
            'due_date'       => now()->addMonth()->format('Y-m-d'),
            'status'         => TransactionStatusEnum::PENDING,
        ];
    }
}
