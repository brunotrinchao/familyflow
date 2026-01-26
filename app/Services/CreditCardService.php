<?php

namespace App\Services;

use App\Enums\InvoiceStatusEnum;
use App\Models\CreditCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreditCardService
{
    /**
     * Cria um novo cartão de crédito.
     *
     * @param array $data
     * @return CreditCard
     * @throws Throwable
     */
    public function create(array $data): CreditCard
    {
        try {
            return DB::transaction(function () use ($data) {
                $card = CreditCard::create($data);

                Log::info('Cartão de crédito criado.', [
                    'card_id' => $card->id,
                    'name'    => $card->name,
                ]);

                return $card;
            });
        } catch (Throwable $e) {
            Log::error('Erro ao criar cartão de crédito.', [
                'message' => $e->getMessage(),
                'data'    => $data,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Atualiza um cartão de crédito existente.
     *
     * @param CreditCard $card
     * @param array $data
     * @return CreditCard
     * @throws Throwable
     */
    public function update(CreditCard $card, array $data): CreditCard
    {
        try {
            return DB::transaction(function () use ($card, $data) {
                $card->update($data);

                Log::info('Cartão de crédito atualizado.', [
                    'card_id' => $card->id,
                    'changes' => $card->getChanges(),
                ]);

                return $card->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Erro ao atualizar cartão de crédito.', [
                'message' => $e->getMessage(),
                'card_id' => $card->id,
                'data'    => $data,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Recalcula o limite utilizado do cartão baseado nas faturas pendentes.
     *
     * @param CreditCard $card
     * @return void
     */
    public function updateUsedLimit(CreditCard $card): void
    {
        try {
            $used = $card->invoices()
                ->whereIn('status', [
                    InvoiceStatusEnum::PENDING,
                    InvoiceStatusEnum::OPEN
                ])
                ->sum('total_amount');

            $card->update(['used' => abs($used)]);

            Log::info('Limite utilizado do cartão atualizado.', [
                'card_id' => $card->id,
                'used'    => $used,
            ]);

        } catch (Throwable $e) {
            Log::error('Erro ao atualizar limite utilizado.', [
                'message' => $e->getMessage(),
                'card_id' => $card->id,
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Incrementa o limite utilizado.
     *
     * @param CreditCard $card
     * @param int $amount
     * @return CreditCard
     */
    public function incrementUsed(CreditCard $card, int $amount): CreditCard
    {
        $card->increment('used', abs($amount));
        return $card->fresh();
    }

    /**
     * Decrementa o limite utilizado.
     *
     * @param CreditCard $card
     * @param int $amount
     * @return CreditCard
     */
    public function decrementUsed(CreditCard $card, int $amount): CreditCard
    {
        $card->decrement('used', abs($amount));
        return $card->fresh();
    }

    /**
     * Verifica se o cartão tem limite disponível.
     *
     * @param CreditCard $card
     * @param int $amount
     * @return bool
     */
    public function hasAvailableLimit(CreditCard $card, int $amount): bool
    {
        $availableLimit = $card->limit - $card->used;
        return $availableLimit >= abs($amount);
    }

    /**
     * Obtém o limite disponível do cartão.
     *
     * @param CreditCard $card
     * @return int
     */
    public function getAvailableLimit(CreditCard $card): int
    {
        $card = $card->fresh();
        return max(0, $card->limit - $card->used);
    }

    /**
     * Obtém a porcentagem de uso do limite.
     *
     * @param CreditCard $card
     * @return float
     */
    public function getUsagePercentage(CreditCard $card): float
    {
        if ($card->limit === 0) {
            return 0;
        }

        return ($card->used / $card->limit) * 100;
    }

    /**
     * Desativa um cartão de crédito.
     *
     * @param CreditCard $card
     * @return CreditCard
     * @throws Throwable
     */
    public function deactivate(CreditCard $card): CreditCard
    {
        try {
            return DB::transaction(function () use ($card) {
                // Verificar se há faturas pendentes
                $hasPendingInvoices = $card->invoices()
                    ->whereIn('status', [
                        InvoiceStatusEnum::PENDING,
                        InvoiceStatusEnum::OPEN
                    ])
                    ->exists();

                if ($hasPendingInvoices) {
                    throw new \Exception(
                        'Não é possível desativar cartão com faturas pendentes.'
                    );
                }

                $card->update(['status' => \App\Enums\StatusEnum::INACTIVE]);

                Log::info('Cartão de crédito desativado.', [
                    'card_id' => $card->id,
                ]);

                return $card->fresh();
            });
        } catch (Throwable $e) {
            Log::error('Erro ao desativar cartão.', [
                'message' => $e->getMessage(),
                'card_id' => $card->id,
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
