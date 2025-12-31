<?php

namespace App\Models;

use App\Enums\BankEnum;
use App\Enums\StatusEnum;
use App\Traits\BelongsToFamily;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class CreditCard extends Model
{
    /** @use HasFactory<\Database\Factories\CreditCardFactory> */
    use HasFactory, BelongsToFamily;

    protected $fillable = [
        'name',
        'last_four_digits',
        'closing_day',
        'due_day',
        'limit',
        'used',
        'status',
        'account_id',
        'brand_id',
        'family_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function familyUser(): BelongsTo
    {
        return $this->belongsTo(FamilyUser::class, 'family_user_id');
    }

}
