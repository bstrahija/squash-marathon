<?php

namespace App\Providers\Filament;

use App\Enums\RoleName;
use App\Models\User;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Models\Contracts\FilamentSocialiteUser as FilamentSocialiteUserContract;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use notwonderful\FilamentMoonlightTheme\MoonlightTheme;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('images/favicon.ico'))
            ->colors([
                'primary' => Color::Amber,
            ]);

        if (class_exists(MoonlightTheme::class)) {
            $panel->plugin(MoonlightTheme::make());
        }

        if (class_exists(AuthDesignerPlugin::class)) {
            $panel->plugin(
                AuthDesignerPlugin::make()
                    ->login(fn (AuthPageConfig $config) => $config
                        ->media(asset('assets/background.jpg'))
                        ->mediaPosition(MediaPosition::Left)
                        ->blur(8)
                    ),
            );
        }

        if (class_exists(FilamentSocialitePlugin::class)) {
            $panel->plugin(
                FilamentSocialitePlugin::make()
                    ->providers([
                        Provider::make('google')
                            ->label('Google'),
                    ])
                    ->registration(fn (string $provider, SocialiteUser $oauthUser, ?Authenticatable $user): bool => $user instanceof User
                        && $user->hasAnyRole([RoleName::Admin->value, RoleName::Player->value]))
                    ->redirectAfterLoginUsing(
                        function (string $provider, FilamentSocialiteUserContract $socialiteUser, FilamentSocialitePlugin $plugin) {
                            $user = $socialiteUser->getUser();

                            if ((int) $user->getAuthIdentifier() === 1) {
                                return redirect()->intended($plugin->getPanel()->getUrl());
                            }

                            return redirect()->route('home')->with('status', 'Prijavljeni ste');
                        },
                    ),
            );
        }

        return $panel
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
                Authenticate::class,
            ]);
    }
}
