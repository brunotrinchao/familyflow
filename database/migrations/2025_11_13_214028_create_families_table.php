<?php

use App\Enums\ProfileUserEnum;
use App\Enums\RoleUserEnum;
use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('family_user', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class);
            $table->foreignIdFor(User::class);
             $table->enum('role', array_column(RoleUserEnum::cases(), 'value'))->default(ProfileUserEnum::ROLE_MEMBER->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
        Schema::dropIfExists('family_user');
    }
};
