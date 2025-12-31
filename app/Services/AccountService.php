<?php

namespace App\Services;

use App\Helpers\MaskHelper;
use App\Models\Account;
use App\Models\Category;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccountService
{

    public static function create(array $data): ?Account
    {

        return Account::create([
            'name'           => $data['name'],
            'balance'        => (int)($data['balance'] ?? 0),
            'brand_id'       => $data['brand_id'],
            'family_user_id' => $data['family_user_id'],
        ]);
    }

    public static function update(Account $account, array $data): Account
    {

        $account->update(array_filter([
            'name'     => $data['name'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'balance'  => MaskHelper::covertStrToInt($data['balance']) ?? null,
        ]));

        return $account;
    }

    /**
     * Ajuste manual de saldo (com registro de auditoria se necessário).
     */
    public function adjustBalance(Account $account, int $newBalance): Account
    {
        $account->update(['balance' => $newBalance]);
        return $account;
    }
}
