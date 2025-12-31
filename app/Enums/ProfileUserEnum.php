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

enum ProfileUserEnum: string implements HasLabel, HasColor, HasIcon
{
    case ROLE_SUPER_ADMIN = 'super_admin';
    case ROLE_ADMIN = 'admin';
    case ROLE_MEMBER = 'member';
    case ROLE_KID = 'kid';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MEMBER => 'Membro',
            self::ROLE_KID => 'Infantil',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ROLE_SUPER_ADMIN => Color::Amber,
            self::ROLE_ADMIN => Color::Green,
            self::ROLE_MEMBER => Color::Cyan,
            self::ROLE_KID => Color::Lime,
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::ROLE_SUPER_ADMIN => Iconoir::UserCrown,
            self::ROLE_ADMIN => Iconoir::UserPlus,
            self::ROLE_MEMBER => Iconoir::User,
            self::ROLE_KID => Iconoir::PeopleTag,
        };
    }
}
