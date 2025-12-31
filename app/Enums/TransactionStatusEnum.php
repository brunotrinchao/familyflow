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
    case PENDING = 'Pending'; // PENDENTE - CRIADA MAS SEM AÇÂO - DEFAULT

    case SCHEDULED = 'Scheduled'; // AGENDADA - DEBITO AUTOMATICO

    case POSTED = 'Posted'; // POSTED - LANÇADA OU SEJA FOI CREDTADA NO CARTAO OU NA CONTA

    case OVERDUE = 'Overdue'; // OVERDUE - ATRASADA - SO PODE SER PASSAR DE SCHEDULED -> OVERDUE

    case CANCELLED = 'Cancelled'; // CANCELED - NAO PODE PASSAR DE POSTED -> CANCELLED

    case CLEARED = 'Cleared'; // CANCELED - NAO PODE PASSAR DE POSTED -> CANCELLED

    case PAID = 'Paid';


    public function getColor(): string|array|null
    {
        return $this->resolveAccount()['color'];
    }

    public function getIcon(): string|BackedEnum|null
    {
        return $this->resolveAccount()['icon'];
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->resolveAccount()['label'];
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
