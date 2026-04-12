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

test('player can log in through web login and is redirected to homepage with status toast', function () {
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

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('status', 'Prijavljeni ste');
    $this->assertAuthenticatedAs($player);
    expect($player->fresh()->last_login_at)->not->toBeNull();
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

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('status', 'Prijavljeni ste');
    $this->assertAuthenticatedAs($player);
    expect($player->fresh()->last_login_at)->not->toBeNull();

    expect(DB::table('socialite_users')
        ->where('provider', 'google')
        ->where('provider_id', 'google-user-123')
        ->where('user_id', $player->id)
        ->exists())->toBeTrue();
});

test('user with id one logs in through web login and is redirected to admin panel', function () {
    $user = User::query()->find(1) ?? User::factory()->create(['id' => 1, 'password' => 'password']);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('logout redirects to homepage with status toast', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('status', 'Odjavljeni ste');
    $this->assertGuest();
});
