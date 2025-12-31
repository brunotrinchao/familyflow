<?php

namespace Database\Factories;

use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\CreditCard;
use App\Models\Family;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       return [
            'family_id'          => Family::factory(),
            'credit_card_id'     => CreditCard::factory(),
            'period_date'        => now()->startOfMonth()->format('Y-m-d'),
            'total_amount' => 0, // Geralmente calculado, mas iniciamos com 0
            'status'             => InvoiceStatusEnum::PENDING,
        ];
    }
}
