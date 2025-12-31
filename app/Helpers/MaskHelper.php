<?php

namespace App\Helpers;

use Filament\Support\RawJs;

class MaskHelper
{

    public static function maskMoney(): RawJs
    {
        return RawJs::make(<<<'JS'
        function ($input) {
            let isNegative = $input.includes('-');
            let value = $input.replace(/[^0-9]/g, '');
            if (value.length === 0) return '';

            value = (parseFloat(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            return (isNegative ? '-' : '') + value;
        }
    JS
        );
    }

    public static function covertIntToReal(int $value = 0, bool $prefix = true): string
    {
        $prefixStr = $prefix ? 'R$ ' : null;
        return $prefixStr . number_format($value / 100, 2, ',', '.');
    }

    public static function covertStrToInt(?string $value): int
    {
        if(empty($value)){
            return 0;
        }

        $cleanAmount = is_string($value)
            ? str_replace([
                '.',
                ','
            ], [
                '',
                '.'
            ], $value) // Substitui '.' por nada e ',' por '.' (ex: '1.000,50' -> '1000.50')
            : $value;
        $value = intval(round((float)$cleanAmount * 100));

        return $value;
    }
}
