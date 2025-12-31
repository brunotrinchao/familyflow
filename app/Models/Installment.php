<?php

namespace App\Models;

use App\Enums\InstallmentStatusEnum;
use App\Enums\TransactionStatusEnum;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'amount',
        'due_date',
        'status',
        'family_id',
        'account_id',
        'transaction_id',
        'invoice_id'
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'integer',
            'due_date' => 'date',
            'status'   => InstallmentStatusEnum::class,
            'number'   => 'integer',
        ];
    }

    // --- Relações ---

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
