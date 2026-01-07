<?php

namespace App\Enums;

use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum StatusGeralEnum: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case POSTED = 'posted';
    case CLEARED = 'cleared';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case OPEN = 'open';
    case CLOSED = 'closed';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case TRIAL = 'trial';
    case LATE_PAYMENT = 'late_payment';
    case REFUNDED = 'refunded';
    case PARTIAL = 'partial';
    case EXPENSE = 'expense';
    case INCOME = 'income';
    case TRANFER = 'transfer';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('custom.status.pending'),
            self::SCHEDULED => __('custom.status.scheduled'),
            self::POSTED => __('custom.status.posted'),
            self::CLEARED => __('custom.status.cleared'),
            self::PAID => __('custom.status.paid'),
            self::OVERDUE => __('custom.status.overdue'),
            self::CANCELLED => __('custom.status.cancelled'),
            self::OPEN => __('custom.status.open'),
            self::CLOSED => __('custom.status.closed'),
            self::ACTIVE => __('custom.status.active'),
            self::INACTIVE => __('custom.status.inactive'),
            self::TRIAL => __('custom.status.trial'),
            self::LATE_PAYMENT => __('custom.status.late_payment'),
            self::REFUNDED => __('custom.status.refunded'),
            self::PARTIAL => __('custom.status.partial'),
            self::EXPENSE => __('custom.type.expense'),
            self::INCOME => __('custom.type.income'),
            self::TRANFER => __('custom.type.transfer'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PAID, self::ACTIVE, self::CLEARED, self::INCOME =>  'success',
            self::PENDING, self::SCHEDULED, self::OPEN, self::TRIAL => Color::Amber,
            self::OVERDUE, self::LATE_PAYMENT, self::EXPENSE => 'danger',
            self::CANCELLED, self::INACTIVE, self::CLOSED, self::TRANFER => 'gray',
            self::POSTED => 'cyan',
            self::REFUNDED => 'violet',
            self::PARTIAL => 'indigo',
            default => Color::Slate,
        };
    }

    public function getIcon(): string|BackedEnum|null
{
    return match ($this) {
        self::PENDING      => Iconoir::ClockSolid,
        self::SCHEDULED    => Iconoir::Calendar,
        self::POSTED       => Iconoir::SendDollars, // Ícone específico para envio/lançamento
        self::CLEARED      => Iconoir::CheckSquareSolid, // Diferencia do PAID por ser uma conferência bancária
        self::PAID         => Iconoir::DoubleCheck,
        self::OVERDUE      => Iconoir::CalendarXmark,
        self::CANCELLED    => Iconoir::Xmark,
        self::OPEN         => Heroicon::LockOpen,
        self::CLOSED       => Heroicon::LockClosed, // Corrigido de Closet (Armário) para Lock (Cadeado)
        self::ACTIVE       => Iconoir::CheckCircleSolid,
        self::INACTIVE     => Iconoir::MinusCircleSolid,
        self::TRIAL        => Iconoir::HistoricShield, // Ícone de proteção/teste
        self::LATE_PAYMENT => Iconoir::WarningTriangleSolid, // Alerta para pagamento atrasado
        self::REFUNDED     => Iconoir::Undo, // Ícone de estorno/retorno
        self::PARTIAL      => Iconoir::Percentage, // Ícone para pagamento parcial
    };
}
}
