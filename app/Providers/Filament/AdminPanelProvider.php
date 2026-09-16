<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
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
            ->id('admin')
            ->path('painel')
            ->brandName('Ferramenta de Transição - GEMar Marcílio Dias - 02BA')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo.svg'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->profile()
            ->collapsibleNavigationGroups(false)
            ->renderHook(
                PanelsRenderHook::SIDEBAR_LOGO_AFTER,
                fn (): string => view('filament.components.brand-nome')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): string => view('filament.components.brand-nome', ['extraClass' => 'hidden sm:flex'])->render(),
            )
            ->colors([
                // Azul marinho da marca do grupo (extraído do logo oficial,
                // #2E3192). Não usa Color::hex() puro porque o algoritmo de
                // geração de paleta do Filament (OKLCH com lightness/chroma
                // fixos por tom) não preserva a cor exata em nenhum tom —
                // o 600 (usado nos botões preenchidos) saía visivelmente
                // mais claro e "lavado" que a marca. Aqui a escada de tons
                // é gerada em HSL a partir da mesma cor, com o 600 fixado
                // no hex exato pra bater com o resto da marca.
                'primary' => [
                    50 => '#F3F4FB',
                    100 => '#E4E4F6',
                    200 => '#C5C6ED',
                    300 => '#9A9CDF',
                    400 => '#6467CE',
                    500 => '#383CB2',
                    600 => '#2E3192',
                    700 => '#222474',
                    800 => '#1A1C59',
                    900 => '#12143E',
                    950 => '#0C0D2A',
                ],
            ])
            ->navigationGroups([
                'Programa Antigo',
                'Programa Novo',
                'Ferramentas',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
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
