<?php

namespace App\Enums;

use App\Enums\Icon\Ionicons;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum InvoiceStatusEnum: string implements HasLabel, HasColor, HasIcon
{
    case OPEN      = 'open';
    case PENDING   = 'pending';
    case SCHEDULED = 'scheduled';
    case OVERDUE   = 'overdue';
    case PAID      = 'paid';
    case PARTIAL   = 'partial';
    case CANCELLED = 'cancelled';
    case CLOSED    = 'closed';



    public function getColor(): string|array|null
    {
        return StatusGeralEnum::from($this->value)->getColor();
    }

    public function getIcon(): string|BackedEnum|null
    {
        return StatusGeralEnum::from($this->value)->getIcon();
    }

    public function getLabel(): string|Htmlable|null
    {
        return StatusGeralEnum::from($this->value)->getLabel();
    }
}
