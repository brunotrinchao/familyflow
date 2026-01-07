<?php

namespace App\Enums;

use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum StatusEnum: string implements HasLabel, HasColor, HasIcon
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function getLabel(): ?string
    {
        return StatusGeralEnum::from($this->value)->getLabel();
    }

    public function getColor(): string|array|null
    {
       return StatusGeralEnum::from($this->value)->getColor();
    }

    public function getIcon(): string|BackedEnum|null
    {
        return StatusGeralEnum::from($this->value)->getIcon();
    }
}
