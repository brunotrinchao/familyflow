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
    case PENDING = 'Pending';
    case POSTED = 'Posted';
    case PAID = 'Paid';
    case OVERDUE = 'Overdue';
    case REFUNDED = 'Refunded';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Aguardando',
            self::POSTED => 'Lançada',
            self::PAID => 'Pago',
            self::OVERDUE => 'Em Atraso',
            self::REFUNDED => 'Estornado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::POSTED => 'info',
            self::PAID =>  'success',
            self::OVERDUE => 'stone',
            self::REFUNDED => 'purple',
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::PENDING => Iconoir::Clock,
            self::POSTED => Iconoir::Check,
            self::PAID => Iconoir::DoubleCheck,
            self::OVERDUE => Iconoir::CalendarXmark,
            self::REFUNDED => Iconoir::MinusCircle,
        };
    }
}
