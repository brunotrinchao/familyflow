<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Contracts\HasColor;

enum CategoryTypeEnum: string implements  HasLabel, HasColor, HasIcon
{
    case EXPENSE = 'expense';
    case INCOME = 'income';
    case TRANSFER = 'transfer';

    public function getLabel(): ?string
    {
        return StatusGeralEnum::from($this->value)->getLabel();
    }

    public function getColor(): string|array|null
    {
        return StatusGeralEnum::from($this->value)->getColor();
    }

    public function getIcon(): string|null|\BackedEnum
    {
        return StatusGeralEnum::from($this->value)->getIcon();
    }
}
