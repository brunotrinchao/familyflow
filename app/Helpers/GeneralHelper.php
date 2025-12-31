<?php

namespace App\Helpers;

use App\Enums\TransactionSourceEnum;

class GeneralHelper
{

    public static function extractSourceEnum(string $sourceCustom): TransactionSourceEnum
    {
        // Pega a parte antes do '__' com segurança
        $prefix = str($sourceCustom)->before('__')->toString();

        // tryFrom retorna null se o valor não existir no Enum, evitando Exceptions
        return TransactionSourceEnum::tryFrom($prefix);
    }

    public static function extractSourceId(string $sourceCustom): int
    {
        return (int)str($sourceCustom)
            ->replace([
                'account__',
                'credit_card__'
            ], '')
            ->toString();
    }

    public static function parseSource(string $sourceCustom): array
    {
        $str = str($sourceCustom);
        return [
            TransactionSourceEnum::tryFrom($str->before('__')),
            (int)$str->afterLast('__')->toString()
        ];
    }
}
