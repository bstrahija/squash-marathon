<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as OAuthUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

test('guest can view web login page', function () {
    $this->withoutVite();

    $response = $this->get(route('login'));

    $response->assertSuccessful();
    $response->assertSee('Prijavite se');
    $response->assertSee(route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']), false);
});

test('google redirect endpoint redirects to social provider', function () {
    $socialiteDriver = Mockery::mock();

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($socialiteDriver);

    $socialiteDriver->shouldReceive('redirectUrl')
        ->once()
        ->andReturnSelf();

    $socialiteDriver->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

    $response = $this->get(route('oauth.google.redirect'));

    $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

test('player can log in through web login and access intended matches page', function () {
    $this->withoutVite();

    Role::firstOrCreate(['name' => RoleName::Player->value]);

    Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    Round::factory()->create([
        'event_id' => Event::query()->latest('id')->value('id'),
        'is_active' => true,
    ]);

    $player = User::factory()->create([
        'password' => 'password',
    ]);
    $player->assignRole(RoleName::Player->value);

    $this->get('/matches/create')->assertRedirect(route('login'));

    $response = $this->post(route('login.store'), [
        'email' => $player->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/matches/create');
    $this->assertAuthenticatedAs($player);
});

test('player cannot access admin panel after web login', function () {
    $this->withoutVite();

    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $response = $this->actingAs($player)->get('/admin');

    $response->assertForbidden();
});

test('google callback logs in existing player by email and stores socialite link', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('google-user-123');
    $oauthUser->shouldReceive('getEmail')->andReturn($player->email);

    $socialiteDriver = Mockery::mock();

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($socialiteDriver);

    $socialiteDriver->shouldReceive('redirectUrl')
        ->once()
        ->andReturnSelf();

    $socialiteDriver->shouldReceive('user')
        ->once()
        ->andReturn($oauthUser);

    $response = $this->get(route('oauth.google.callback'));

    $response->assertRedirect(route('matches.index'));
    $this->assertAuthenticatedAs($player);

    expect(DB::table('socialite_users')
        ->where('provider', 'google')
        ->where('provider_id', 'google-user-123')
        ->where('user_id', $player->id)
        ->exists())->toBeTrue();
});
