<?php

namespace App\Enums;

use App\Enums\Icon\Ionicons;
use BackedEnum;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum NavigationGroupEnum: string implements HasLabel, HasIcon
{
    case Settings = 'settings';
    case Admin = 'admin';

    //    case Blog;
    //
    //    case Settings;

    public function getLabel(): string
    {
        return $this->resolveNavigationGroups()['label'];
    }

    public function getIcon(): string|BackedEnum|null
    {
        return $this->resolveNavigationGroups()['icon'];
    }

    private function resolveNavigationGroups(): array
    {
        return match ($this) {
            self::Settings => [
                'label' => __('custom.navigation-groups.settings'),
                'icon' => Iconoir::Settings,
            ],
            self::Admin => [
                'label' => __('custom.navigation-groups.admin'),
                'icon' => Iconoir::Lock,
            ]
            //            self::Blog => __('navigation-groups.blog'),
            //            self::Settings => __('navigation-groups.settings'),
        };
    }
}
