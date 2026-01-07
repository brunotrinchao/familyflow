<?php

namespace App\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum UserStatusEnum: string implements HasLabel, HasColor
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
}
