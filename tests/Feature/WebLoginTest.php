<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as OAuthUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
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
        'end_at'   => now()->addHour(),
    ]);

    Round::factory()->create([
        'event_id'  => Event::query()->latest('id')->value('id'),
        'is_active' => true,
    ]);

    $player = User::factory()->create([
        'password' => 'password',
    ]);
    $player->assignRole(RoleName::Player->value);

    $this->get('/matches/create')->assertRedirect(route('login'));

    $response = $this->post(route('login.store'), [
        'email'    => $player->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('status', 'Prijavljeni ste');
    $response->assertCookie(Auth::guard('web')->getRecallerName());
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

test('user with id one logs in through web login and is redirected to homepage', function () {
    $user = User::query()->find(1) ?? User::factory()->create(['id' => 1, 'password' => 'password']);

    $response = $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('status', 'Prijavljeni ste');
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

test('wrong password returns validation error on email field', function () {
    $user = User::factory()->create(['password' => 'correct-password']);

    $response = $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('google callback with invalid state redirects to login with error', function () {
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
        ->andThrow(new InvalidStateException);

    $response = $this->get(route('oauth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
});

test('google callback with unknown email redirects to login with error', function () {
    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('unknown-google-id');
    $oauthUser->shouldReceive('getEmail')->andReturn('nobody@example.com');

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

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('google callback for user without role redirects to login with error', function () {
    // User exists in the DB but has no role
    $user = User::factory()->create();

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('norole-google-id');
    $oauthUser->shouldReceive('getEmail')->andReturn($user->email);

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

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('google callback updates existing socialite_users row rather than inserting a duplicate', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    // Pre-insert a socialite_users row
    DB::table('socialite_users')->insert([
        'provider'    => 'google',
        'provider_id' => 'existing-google-id',
        'user_id'     => $player->id,
        'created_at'  => now()->subDay(),
        'updated_at'  => now()->subDay(),
    ]);

    $oauthUser = Mockery::mock(OAuthUser::class);
    $oauthUser->shouldReceive('getId')->andReturn('existing-google-id');
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

    $this->get(route('oauth.google.callback'));

    // Only one row should exist — not two
    expect(DB::table('socialite_users')
        ->where('provider', 'google')
        ->where('provider_id', 'existing-google-id')
        ->count())->toBe(1);
});

test('EnsureUserCanManageMatches blocks users without any role', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('matches.create'));

    $response->assertForbidden();
});

test('EnsureUserCanManageMatches allows players through', function () {
    $this->withoutVite();

    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $response = $this->actingAs($player)->get(route('matches.create'));

    $response->assertSuccessful();
});

test('EnsureUserCanManageMatches allows admins through', function () {
    $this->withoutVite();

    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $response = $this->actingAs($admin)->get(route('matches.create'));

    $response->assertSuccessful();
});
