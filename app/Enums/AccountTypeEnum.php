<?php

namespace App\Enums;

use App\Enums\Icon\Ionicons;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum AccountTypeEnum: string implements HasLabel
{
    case CC = 'current_account';

    case CP = 'savings_account';


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
            self::CC => [
                'label' => __('custom.type.current_account'),
                'icon' => Iconoir::Cash,
                'color' => 'primary',
            ],
            self::CP => [
                'label' => __('custom.type.savings_account'),
                'icon' => Iconoir::Coins,
                'color' => 'success',
            ]
        };
    }
}
