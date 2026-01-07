<?php

namespace App\Enums;

use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum InstallmentStatusEnum: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case POSTED = 'posted';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case REFUNDED = 'refunded';

    public function getLabel(): ?string
    {
        return StatusGeralEnum::from($this->value)->getLabel();
    }

    public function getColor(): string|array|null
    {
        return StatusGeralEnum::from($this->value)->getColor();
    }

    public function getIcon(): string|BackedEnum|null
    {
        return StatusGeralEnum::from($this->value)->getIcon();
    }
}
