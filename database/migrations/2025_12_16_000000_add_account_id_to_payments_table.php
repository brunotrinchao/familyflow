<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignIdFor(Account::class)
                ->nullable()
                ->after('transaction_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY transaction_id BIGINT UNSIGNED NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payments ALTER COLUMN transaction_id DROP NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY transaction_id BIGINT UNSIGNED NOT NULL');
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE payments ALTER COLUMN transaction_id SET NOT NULL');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Account::class);
        });
    }
};
