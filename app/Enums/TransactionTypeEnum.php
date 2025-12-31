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

enum TransactionTypeEnum: string implements HasLabel, HasColor, HasIcon
{
    case EXPENSE = 'expense';

    case INCOME = 'income';

    case TRANSFER = 'transfer';


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
            self::EXPENSE => [
                'label' => __('custom.type.expense'),
                'icon' => Iconoir::Minus,
                'color' => Color::Red,
            ],
            self::INCOME => [
                'label' => __('custom.type.income'),
                'icon' => Iconoir::Plus,
                'color' => Color::Green,
            ],
            self::TRANSFER => [
                'label' => __('custom.type.transfer'),
                'icon' => Iconoir::Shuffle,
                'color' => Color::Gray,
            ]
        };
    }
}
