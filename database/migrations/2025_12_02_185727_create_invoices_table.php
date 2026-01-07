<?php

use App\Models\CreditCard;
use App\Models\Family;
use App\Models\FamilyUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)->constrained();

            $table->foreignIdFor(CreditCard::class)->constrained()->cascadeOnDelete();

            // Mês e Ano da fatura (Ex: 2025-12-01)
            $table->date('period_date');

            // Valor total agregado das parcelas deste mês (calculado no Service)
            $table->bigInteger('total_amount')->default(0);

            // Status do pagamento desta fatura (ex: PAID, PENDING, OVERDUE)
            $table->enum('status', array_column(\App\Enums\InvoiceStatusEnum::cases(), 'value'))->default(\App\Enums\InvoiceStatusEnum::PENDING);

            $table->timestamps();

            // Unique constraint
            $table->unique(['credit_card_id', 'period_date']);

            // Índices
            $table->index('family_id');
            $table->index('credit_card_id');
            $table->index('period_date');
            $table->index('status');
            $table->index(['family_id', 'period_date']);
            $table->index(['family_id', 'status']);
            $table->index(['credit_card_id', 'period_date']);
            $table->index(['status', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
