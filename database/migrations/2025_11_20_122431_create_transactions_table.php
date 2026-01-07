<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\CreditCard;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_user_id')->constrained('family_user');
            $table->foreignIdFor(Category::class)->constrained();
            $table->foreignIdFor(Account::class)->nullable()->constrained();
            $table->foreignIdFor(CreditCard::class)->nullable()->constrained();
            $table->unsignedBigInteger('destination_account_id')->nullable();


            $table->enum('source', array_column(\App\Enums\TransactionSourceEnum::cases(), 'value'));
            $table->enum('type', array_column(\App\Enums\TransactionTypeEnum::cases(), 'value'));
            $table->date('date');
            $table->bigInteger('amount')->default(0);
            $table->integer('installment_number')->default(1);
            $table->string('title');
            $table->string('description')->nullable();
            $table->enum('status', array_column(\App\Enums\TransactionStatusEnum::cases(), 'value'))->default(\App\Enums\TransactionStatusEnum::PENDING);

            $table->timestamps();
            $table->softDeletes(); // Recomendado para auditoria financeira

            // Índices simples
            $table->index('family_user_id');
            $table->index('category_id');
            $table->index('account_id');
            $table->index('credit_card_id');
            $table->index('destination_account_id');
            $table->index('source');
            $table->index('type');
            $table->index('date');
            $table->index('status');
            $table->index('deleted_at');

            // Índices compostos para queries comuns
            $table->index(['family_user_id', 'date']);
            $table->index(['family_user_id', 'type', 'date']);
            $table->index(['family_user_id', 'status']);
            $table->index(['category_id', 'date']);
            $table->index(['account_id', 'date']);
            $table->index(['credit_card_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
