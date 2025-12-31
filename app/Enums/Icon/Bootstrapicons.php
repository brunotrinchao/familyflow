<?php

namespace App\Enums\Icon;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum Bootstrapicons: string implements HasLabel, HasIcon
{
    case PIGGY_BANK = 'bi-piggy-bank';
    case SAFE = 'bi-safe2-fill';


    public function getIcon(): string|BackedEnum|null
    {
        return $this->name;
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PIGGY_BANK => 'Conta Poupança',
            self::SAFE => 'Conta Corrente',
        };
    }
}
