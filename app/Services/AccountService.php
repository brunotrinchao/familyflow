<?php

namespace App\Services;

use App\Helpers\MaskHelper;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccountService
{
    /**
     * Cria uma nova conta bancária.
     *
     * @param array $data
     * @return Account
     * @throws Throwable
     */
    public function create(array $data): Account
    {
        try {
            return DB::transaction(function () use ($data) {
                return Account::create([
                    'name'           => $data['name'],
                    'balance'        => (int)($data['balance'] ?? 0),
                    'brand_id'       => $data['brand_id'],
                    'family_user_id' => $data['family_user_id'],
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Erro ao criar conta.', [
                'message' => $e->getMessage(),
                'data'    => $data,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza uma conta existente.
     *
     * @param Account $account
     * @param array $data
     * @return Account
     * @throws Throwable
     */
    public function update(Account $account, array $data): Account
    {
        try {
            return DB::transaction(function () use ($account, $data) {
                $updateData = array_filter([
                    'name'     => $data['name'] ?? null,
                    'brand_id' => $data['brand_id'] ?? null,
                    'balance'  => isset($data['balance'])
                        ? MaskHelper::covertStrToInt($data['balance'])
                        : null,
                ], fn($value) => $value !== null);

                $account->update($updateData);

                return $account->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Erro ao atualizar conta.', [
                'message'    => $e->getMessage(),
                'account_id' => $account->id,
                'data'       => $data,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Ajusta manualmente o saldo de uma conta.
     *
     * @param Account $account
     * @param int $newBalance
     * @return Account
     * @throws Throwable
     */
    public function adjustBalance(Account $account, int $newBalance): Account
    {
        try {
            return DB::transaction(function () use ($account, $newBalance) {
                $oldBalance = $account->balance;

                $account->update(['balance' => $newBalance]);

                Log::info('Saldo de conta ajustado manualmente.', [
                    'account_id'  => $account->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'difference'  => $newBalance - $oldBalance,
                ]);

                return $account->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Erro ao ajustar saldo da conta.', [
                'message'     => $e->getMessage(),
                'account_id'  => $account->id,
                'new_balance' => $newBalance,
                'trace'       => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Incrementa o saldo da conta.
     *
     * @param Account $account
     * @param int $amount
     * @return Account
     */
    public function incrementBalance(Account $account, int $amount): Account
    {
        $account->increment('balance', $amount);
        return $account->fresh();
    }

    /**
     * Decrementa o saldo da conta.
     *
     * @param Account $account
     * @param int $amount
     * @return Account
     */
    public function decrementBalance(Account $account, int $amount): Account
    {
        $account->decrement('balance', $amount);
        return $account->fresh();
    }

    /**
     * Verifica se a conta tem saldo suficiente.
     *
     * @param Account $account
     * @param int $amount
     * @return bool
     */
    public function hasSufficientBalance(Account $account, int $amount): bool
    {
        return $account->balance >= $amount;
    }

    /**
     * Obtém o saldo atual da conta.
     *
     * @param Account $account
     * @return int
     */
    public function getBalance(Account $account): int
    {
        return $account->fresh()->balance;
    }
}
