<?php

namespace App\Filament\Pages;

use App\Enums\Icon\Ionicons;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|null|\BackedEnum $navigationIcon = Iconoir::DashboardSpeed;
//    protected string $view = 'filament.pages.dashboard';
}
