<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\CreditCard;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
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
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();

            // --- Multi-Tenancy (Escopo de Família) ---
            $table->foreignId('family_user_id')->constrained('family_user');

            $table->foreignIdFor(Brand::class)->constrained();

            $table->foreignIdFor(Account::class)->constrained();

            $table->string('name'); // Nome amigável (ex: "Cartão da Maria", "Cartão Principal")
            $table->string('last_four_digits', 4)->nullable(); // Últimos 4 dígitos (para identificação)

            $table->integer('closing_day'); // Dia do mês em que a fatura fecha (ex: 25)
            $table->integer('due_day'); // Dia do mês em que a fatura vence (ex: 5)

            $table->integer('limit')->default(0.00);
            $table->integer('used')->default(0.00);
            $table->enum('status', array_column(\App\Enums\StatusEnum::cases(), 'value'))->default(\App\Enums\StatusEnum::ACTIVE);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
