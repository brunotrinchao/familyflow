<?php

namespace App\Enums;

use App\Enums\Icon\Ionicons;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionStatusEnum: string implements HasLabel, HasColor, HasIcon
{
    case PENDING    = 'pending';
    case SCHEDULED  = 'scheduled';
    case POSTED     = 'posted';
    case OVERDUE    = 'overdue';
    case CANCELLED  = 'cancelled';
    case CLEARED    = 'cleared';
    case PAID       = 'paid';


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

    private function resolveAccount(): array
    {
        return match ($this) {
            self::PENDING => [
                'label' => __('custom.type.pending'),
                'icon'  => Iconoir::ClockSolid,
                'color' => Color::Neutral,
            ],
            self::SCHEDULED => [
                'label' => __('custom.type.scheduled'),
                'icon'  => Iconoir::Calendar,
                'color' => Color::Yellow,
            ],
            self::POSTED => [
                'label' => __('custom.type.posted'),
                'icon'  => Iconoir::Check,
                'color' => Color::Cyan,
            ],
            self::OVERDUE => [
                'label' => __('custom.type.overdue'),
                'icon'  => Iconoir::CalendarXmark,
                'color' => Color::Red,
            ],
            self::CANCELLED => [
                'label' => __('custom.type.canceled'),
                'icon'  => Iconoir::MinusCircle,
                'color' => Color::Amber,
            ],
            self::PAID => [
                'label' => __('custom.type.paid'),
                'icon'  => Iconoir::DoubleCheck,
                'color' => Color::Green,
            ]
        };
    }
}
