<?php

use App\Enums\CategoryTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Models\Family;
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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->nullable()->constrained();
            $table->string('name');
            $table->string('icon');
            $table->enum('type',array_column(TransactionTypeEnum::cases(), 'value'));
            $table->string('color');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['name', 'family_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
