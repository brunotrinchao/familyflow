<?php

namespace App\Models;

use App\Enums\FamilyStatusEnum;
use App\Enums\RoleUserEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;

class Family extends Model
{
    /** @use HasFactory<\Database\Factories\FamilyFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'families';

    protected $fillable = [
        'name',
        'slug',
        'status'
    ];

    protected $casts = [
        'status' => FamilyStatusEnum::class
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Relacionamento com usuários através da pivot family_user
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(FamilyUser::class)
            ->withPivot('id', 'role')
            ->withTimestamps()
            ->wherePivot('role', '!=', RoleUserEnum::ROLE_SUPER_ADMIN->value);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'family_user')
            ->using(FamilyUser::class) // Indica que você usa a classe pivot customizada
            ->withPivot('id', 'role')       // Garante acesso à coluna role
            ->wherePivot('role', RoleUserEnum::ROLE_ADMIN->value);
    }

}
