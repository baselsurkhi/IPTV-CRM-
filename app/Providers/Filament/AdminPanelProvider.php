<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditProfilePage;
use App\Filament\Pages\OperationsDashboard;
use App\Http\Middleware\SetFilamentLocale;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->defaultThemeMode(ThemeMode::Light)
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfilePage::class)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->brandLogo(fn () => view('admin.brand'))
            ->font('Tajawal', url: asset('fonts/studio/font.css'), provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('admin.sidebar-footer'))
            ->renderHook(PanelsRenderHook::SIMPLE_PAGE_START, fn () => view('admin.login-intro'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('admin.theme'))
            ->renderHook(PanelsRenderHook::PAGE_START, fn () => view('admin.workspace-banner'))
            ->brandName(fn (): string => __('admin.brand'))
            ->colors([
                'primary' => Color::Violet,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                OperationsDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([

            ])
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => view('admin.language-switcher'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetFilamentLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
