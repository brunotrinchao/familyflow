<?php

namespace App\Enums;

enum FamilyStatusEnum: string
{
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case LATE_PAYMENT = 'late_payment';
    case INACTIVE = 'inactive';

    case PAYMENT_PENDING = 'payment_pending';

    // Opcional: Método para verificar se está ativo para acesso
    public function isAccessAllowed(): bool
    {
        return match ($this) {
            self::ACTIVE, self::TRIAL, self::LATE_PAYMENT => true,
            default => false,
        };
    }
}
