<?php

namespace App\Services;

use App\Models\Account;
use App\Models\CreditCard;

class BalanceService
{
    public function applyAccountDelta(Account $account, int $amount): void
    {
        if ($amount === 0) {
            return;
        }

        if ($amount > 0) {
            $account->increment('balance', $amount);
            return;
        }

        $account->decrement('balance', abs($amount));
    }

    public function applyCreditCardUsedDelta(CreditCard $card, int $amount): void
    {
        if ($amount === 0) {
            return;
        }

        if ($amount > 0) {
            $card->increment('used', $amount);
            return;
        }

        $card->decrement('used', abs($amount));
    }
}
