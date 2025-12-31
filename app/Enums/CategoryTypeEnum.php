<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Contracts\HasColor;

enum CategoryTypeEnum: string implements  HasLabel, HasColor
{
    case EXPENSE = 'expense';
    case INCOME = 'income';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::EXPENSE => 'Despesa',
            self::INCOME => 'Receita'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EXPENSE => 'danger',
            self::INCOME => 'success'
        };
    }
}
