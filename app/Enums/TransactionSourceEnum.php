<?php

namespace App\Enums;

use App\Enums\Icon\Ionicons;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionSourceEnum: string implements HasLabel
{
    case ACCOUNT = 'account';

    case CREDIT_CARD = 'credit_card';


//    public function getColor(): string|array|null
//    {
//        return $this->resolveAccount()['color'];
//    }
//
//    public function getIcon(): string|BackedEnum|null
//    {
//        return $this->resolveAccount()['icon'];
//    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->resolveAccount()['label'];
    }

    private function resolveAccount(): array
    {
        return match ($this) {
            self::ACCOUNT => [
                'label' => __('custom.type.account'),
                'icon' => Iconoir::Bank,
                'color' => 'primary',
            ],
            self::CREDIT_CARD => [
                'label' => __('custom.type.credit_card'),
                'icon' => Iconoir::CreditCard,
                'color' => 'success',
            ]
        };
    }
}
