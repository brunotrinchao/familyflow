<?php

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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // 2. Chave Estrangeira: Invoice (a fatura que foi paga)
            $table->foreignIdFor(Invoice::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            // 3. Chave Estrangeira: Transaction (o débito real na conta/saída de dinheiro)
            $table->foreignIdFor(Transaction::class)
                ->constrained()
                ->cascadeOnDelete();

            // Valor pago (para permitir pagamentos parciais)
            $table->bigInteger('amount');

            // Data em que o pagamento foi registrado/efetuado
            $table->date('paid_at');

            // Status do pagamento (PAGO, CANCELADO, PROCESSANDO)
             $table->enum('status', array_column(\App\Enums\PaymentStatusEnum::cases(), 'value'))->default(\App\Enums\PaymentStatusEnum::POSTED);

            $table->timestamps();

             // Índices
            $table->index('invoice_id');
            $table->index('transaction_id');
            $table->index('paid_at');
            $table->index('status');
            $table->index(['invoice_id', 'status']);
            $table->index(['paid_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
