<?php

namespace App\Providers;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Family;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use TomatoPHP\FilamentIcons\Facades\FilamentIcons;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
//        Cashier::useCustomerModel(Family::class);
        CategoryResource::scopeToTenant(false);
    }
}
