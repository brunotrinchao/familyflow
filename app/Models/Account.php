<?php

namespace App\Models;

use App\Enums\AccountTypeEnum;
use App\Enums\BankEnum;
use App\Traits\BelongsToFamily;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Account extends Model
{

    use HasFactory, BelongsToFamily;

    protected $fillable = [
        'name',
        'balance',
        'brand_id',
        'family_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountTypeEnum::class,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function familyUser(): BelongsTo
    {
        return $this->belongsTo(FamilyUser::class, 'family_user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }


}
