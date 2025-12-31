<?php

namespace App\Models;

use App\Enums\InvoiceStatusEnum;
use App\Enums\TransactionStatusEnum;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    // Implementa a trait de Tenant

    protected $fillable = [
        'period_date',
        'total_amount',
        'status',
        'family_id',
        'credit_card_id'
    ];

    protected function casts(): array
    {
        return [
            'period_date'        => 'date',
            'total_amount_cents' => 'integer',
            'status'             => InvoiceStatusEnum::class,
        ];
    }

    // --- Relações ---
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

    public function refreshTotalAmount()
    {
        // A soma automática das parcelas (já com sinais invertidos no banco)
        // Se Expense é -100 e Income é +100, a soma dará 0.
        $this->total_amount = $this->installments()->sum('amount');
        $this->save();
    }
}
