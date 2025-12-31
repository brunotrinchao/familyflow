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
    case OPEN      = 'Open';      // Fatura do mês atual recebendo lançamentos
    case PENDING   = 'Pending';   // Fatura fechada aguardando vencimento
    case SCHEDULED = 'Scheduled'; // Pagamento já agendado no banco
    case OVERDUE   = 'Overdue';   // Passou da data de vencimento e não foi paga
    case PAID      = 'Paid';      // Totalmente quitada
    case PARTIAL   = 'Partial';   // Paga parcialmente (gera crédito rotativo)
    case CANCELLED = 'Cancelled'; // Fatura anulada (ajustes manuais)
    case CLOSED    = 'Closed';



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
            self::OPEN => [
                'label' => 'Aberta',
                'icon' => Heroicon::LockOpen,
                'color' => 'stone',
            ],
            self::PENDING => [
                'label' => __('custom.type.pending'),
                'icon' => Heroicon::LockClosed,
                'color' => 'warning',
            ],
            self::SCHEDULED => [
                'label' => 'Agendada',
                'icon' => Heroicon::CalendarDays,
                'color' => 'gray',
            ],
            self::OVERDUE => [
                'label' => __('custom.type.overdue'),
                'icon' => Heroicon::ExclamationTriangle,
                'color' => 'danger',
            ],
            self::PAID => [
                'label' => 'Paga',
                'icon' => Iconoir::DoubleCheck,
                'color' => 'success',
            ],
            self::PARTIAL => [
                'label' => 'Pagamento Parcial',
                'icon' => Heroicon::Banknotes,
                'color' => 'info',
            ],
            self::CANCELLED => [
                'label' => 'Cancelada',
                'icon' => Heroicon::XCircle,
                'color' => 'purple',
            ],
            self::CLOSED => [
                'label' => 'Fechada',
                'icon' => Iconoir::Lock,
                'color' => 'stone',
            ],
        };
    }
}
