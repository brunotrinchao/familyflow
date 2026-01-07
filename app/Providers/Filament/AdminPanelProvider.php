<?php

namespace App\Providers\Filament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use App\Enums\NavigationGroupEnum;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Tenancy\CustomRegister;
use App\Filament\Pages\Tenancy\RegisterFamily;
use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\CreditCards\CreditCardResource;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Http\Middleware\SetFamilyContext;
use App\Http\Middleware\TenantMiddleware;
use App\Models\Family;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Enums\AuthLayout;
use Caresome\FilamentAuthDesigner\Enums\MediaDirection;
use CharrafiMed\GlobalSearchModal\GlobalSearchModalPlugin;
use Filafly\Icons\Iconoir\Enums\Iconoir;
use Filafly\Icons\Iconoir\IconoirIcons;
use Filafly\Themes\Brisk\BriskTheme;
use Filament\Actions\Action;
use Filament\Billing\Providers\SparkBillingProvider;
use Filament\Enums\ThemeMode;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Hydrat\TableLayoutToggle\TableLayoutTogglePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->font('Exo')
            ->colors([
                'primary' => Color::Blue[400],
                'danger'  => Color::Red[400],
                'gray'    => Color::Gray,
                'info'    => Color::Sky[400],
                'success' => Color::Green[400],
                'warning' => Color::Yellow[400],
                'stone'   => Color::Stone[400],
                'purple'  => Color::Purple[400],
                'cyan'    => Color::Cyan[400],
                'violet'  => Color::Violet[400],
                'indigo'  => Color::Indigo[400],
                'slate'   => Color::Slate[400]
            ])
            ->profile(isSimple: true)
            ->defaultThemeMode(ThemeMode::Light)
            ->breadcrumbs(false)

            ->simplePageMaxContentWidth(Width::Medium)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                //                AccountWidget::class,
                //                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class
            ])
            ->registration(RegisterFamily::class)
            ->passwordReset()
            ->emailVerification()
            ->tenant(Family::class, slugAttribute: 'slug')
            ->tenantMenu(false)
            ->tenantMiddleware([
                SetFamilyContext::class,
            ], isPersistent: true)
            //            ->tenantBillingProvider(new BillingProvider('default'))
            //            ->requiresTenantSubscription()
            ->navigationGroups(NavigationGroupEnum::class)
            ->databaseTransactions()
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER, // Ou TOPBAR_START
                fn (): string => Blade::render('@livewire("global-transaction-button")')
            )
            //            ->renderHook( DESCOMENTAR QUANDO ADICIONAR BILOING
            //                PanelsRenderHook::TOPBAR_AFTER,
            //
            //                // Renderiza a view customizada
            //                fn () => Blade::render('@include(\'filament.custom.trial-sidebar-message\')'),
            //            )
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'CTRL+K',
                Platform::Mac => '⌘K',
                default => null,
            })
            ->globalSearchKeyBindings([
                'command+k',
                'ctrl+k'
            ])
            ->userMenuItems([
                'family' => Action::make('w')
                    ->label('Familia')
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon(Iconoir::Group),
                //                'subscribe' => Action::make('t')
                //                    ->label('Gerenciar Assinatura')
                //                    ->url(fn (): string => Filament::getTenant()->slug . '/billing')
                //                    ->icon(Iconoir::Bell),
            ])
            ->plugins([
                BriskTheme::make(),
                IconoirIcons::make(),
                GlobalSearchModalPlugin::make()
                    ->keepFooterView(false)
                    ->scopes([
                        CreditCardResource::class,
                        TransactionResource::class,
                        AccountResource::class
                    ])
                    ->showGroupSearchCounts(),
                TableLayoutTogglePlugin::make()
                    ->setDefaultLayout()
                    ->shareLayoutBetweenPages(true) // allow all tables to share the layout option for this user
                    ->displayToggleAction() // used to display the toggle action button automatically
                    ->toggleActionHook('tables::toolbar.search.after') // chose the Filament view hook to render the button on
                    ->listLayoutButtonIcon("iconoir-" . Iconoir::TableRows->value)
                    ->gridLayoutButtonIcon("iconoir-" . Iconoir::ViewGrid->value)
                    ->displayToggleAction(false),
                FilamentEditProfilePlugin::make()
                    ->slug('profile')
                    ->setTitle('Perfil')
                    ->setNavigationLabel('Perfil')
                    ->setIcon(Iconoir::User)
                    ->setSort(10)
                    ->shouldRegisterNavigation(false)
                    ->shouldShowEmailForm()
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm()
                    ->shouldShowAvatarForm(
                        value    : true,
                        directory: 'avatars',
                        rules    : 'mimes:jpeg,png|max:1024'
                    ),
            ]);
    }
}

