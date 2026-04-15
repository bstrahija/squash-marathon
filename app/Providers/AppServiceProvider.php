<?php

namespace App\Providers;

use App\Filament\Auth\LoginResponse as FilamentLoginResponse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as FilamentLoginResponseContract;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FilamentLoginResponseContract::class, FilamentLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerLoginTracking();
        $this->configureDefaults();
    }

    protected function registerLoginTracking(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            if (! ($event->user instanceof User)) {
                return;
            }

            $event->user->forceFill([
                'last_login_at' => now(),
            ])->save();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (app()->environment('local') && filled(config('app.local_simulated_now'))) {
            Date::setTestNow(
                CarbonImmutable::parse((string) config('app.local_simulated_now'), config('app.timezone')),
            );
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
