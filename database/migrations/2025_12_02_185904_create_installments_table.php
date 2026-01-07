<?php

use App\Models\Account;
use App\Models\Family;
use App\Models\Invoice;
use App\Models\Transaction;
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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();

            // O Cliente (Family)
            $table->foreignIdFor(Family::class)->constrained()->cascadeOnDelete();

            // Link para a Transação original (Compra de 12x)
            $table->foreignIdFor(Transaction::class)->constrained()->cascadeOnDelete();

            // 🚨 Link para a Fatura (Invoice) se for um lançamento de Cartão
            $table->foreignIdFor(Invoice::class)->nullable()->constrained()->cascadeOnDelete();

            // Link para a Conta/Cartão (para lançamentos de conta ou débito)
            $table->foreignIdFor(Account::class)->nullable()->constrained()->cascadeOnDelete();

            $table->integer('number'); // 1 de N, 2 de N, etc.
            $table->bigInteger('amount'); // Valor da parcela (em centavos)
            $table->date('due_date'); // Data de vencimento/lançamento real

            $table->enum('status', array_column(\App\Enums\TransactionStatusEnum::cases(), 'value'))->default(\App\Enums\TransactionStatusEnum::PENDING);

            $table->timestamps();

            // Índices simples
            $table->index('family_id');
            $table->index('transaction_id');
            $table->index('invoice_id');
            $table->index('account_id');
            $table->index('due_date');
            $table->index('status');
            $table->index('number');

            // Índices compostos para queries comuns
            $table->index(['transaction_id', 'number']);
            $table->index(['invoice_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index(['family_id', 'due_date']);
            $table->index(['family_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};
