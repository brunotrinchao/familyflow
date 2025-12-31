<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\TransactionSourceEnum;
use App\Traits\BelongsToFamily;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory, BelongsToFamily;

    protected $fillable = [
        'category_id',
        'family_user_id',
        'credit_card_id',
        'destination_account_id',
        'account_id',
        'source',
        'type',
        'date',
        'amount',
        'title',
        'description',
        'status',
        'installment_number',
    ];

    protected $casts = [
        'date'   => 'date',
        'source' => TransactionSourceEnum::class,
        'type'   => TransactionTypeEnum::class,
        'status' => TransactionStatusEnum::class,
        'amount' => 'int',
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function familyUser(): BelongsTo
    {
        return $this->belongsTo(FamilyUser::class, 'family_user_id');
    }
}
