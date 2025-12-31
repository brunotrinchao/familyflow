<?php

namespace App\Services;

use App\Enums\InvoiceStatusEnum;
use App\Helpers\MaskHelper;
use App\Models\CreditCard;
use Illuminate\Support\Arr;

class CreditCardService
{
    public function create(array $data): CreditCard
    {
        return CreditCard::create($data);
    }

    /**
     * Recalcula o limite utilizado do cartão baseado nas faturas pendentes.
     */
    public function updateUsedLimit(CreditCard $card): void
    {
        $used = $card->invoices()
            ->whereIn('status', [InvoiceStatusEnum::PENDING, InvoiceStatusEnum::OPEN])
            ->sum('total_amount');

        $card->update(['used' => $used]);
    }
}
