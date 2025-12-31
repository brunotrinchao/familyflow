<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ProfileUserEnum;
use App\Enums\RoleUserEnum;
use App\Enums\UserStatusEnum;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile',
        'status',
        'locale',
        'theme_color',
        'avatar_url',
        'custom_fields'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'profile'           => ProfileUserEnum::class,
            'status'            => UserStatusEnum::class,
            'custom_fields'     => 'array'
        ];
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class)
            ->using(FamilyUser::class)
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    public function familyUsers(): HasMany
    {
        return $this->hasMany(FamilyUser::class, 'user_id');
    }


    public function getTenants(Panel $panel): Collection
    {
        return $this->families;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->families->contains($tenant);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        $familyId ??= Filament::getTenant()?->id;

        if (!$familyId) {
            return false;
        }

        return $this->families()
            ->where('families.id', $familyId)
            ->wherePivot('role', RoleUserEnum::ROLE_SUPER_ADMIN)
            ->exists();
    }

    public function isAdmin(): bool
    {
        $familyId ??= Filament::getTenant()?->id;

        if (!$familyId) {
            return false;
        }

        return $this->families()
            ->where('families.id', $familyId)
            ->wherePivot('role', RoleUserEnum::ROLE_ADMIN)
            ->exists();
    }

    public function isMember(): bool
    {
        $familyId ??= Filament::getTenant()?->id;

        if (!$familyId) {
            return false;
        }

        return $this->families()
            ->where('families.id', $familyId)
            ->wherePivot('role', RoleUserEnum::ROLE_MEMBER)
            ->exists();
    }

    public function canImpersonate()
    {
        return self::isSuperAdmin();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');
        return $this->$avatarColumn ? Storage::url($this->$avatarColumn) : null;
    }

    //    public static function boot(): void
    //    {
    //        Cashier::useCustomerModel(Family::class);
    //    }
}
