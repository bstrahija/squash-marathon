<?php

use App\Enums\RoleName;
use App\Models\User;
use Filament\Panel;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// fullName attribute
// ---------------------------------------------------------------------------

test('full name trims surrounding whitespace', function () {
    $user = User::factory()->make(['first_name' => '  Ana', 'last_name' => 'Horvat  ']);

    expect($user->full_name)->toBe('Ana Horvat');
});

test('full name concatenates first and last name', function () {
    $user = User::factory()->make(['first_name' => 'Ana', 'last_name' => 'Horvat']);

    expect($user->full_name)->toBe('Ana Horvat');
});

// ---------------------------------------------------------------------------
// shortName attribute
// ---------------------------------------------------------------------------

test('short name returns Igrac when both names are empty', function () {
    $user = User::factory()->make(['first_name' => '', 'last_name' => '']);

    expect($user->short_name)->toBe('Igrac');
});

test('short name returns first initial only when there is no last name', function () {
    $user = User::factory()->make(['first_name' => 'Marko', 'last_name' => '']);

    expect($user->short_name)->toBe('M.');
});

test('short name returns first initial with last name', function () {
    $user = User::factory()->make(['first_name' => 'Marko', 'last_name' => 'Horvat']);

    expect($user->short_name)->toBe('M. Horvat');
});

test('short name handles multi-word last name', function () {
    $user = User::factory()->make(['first_name' => 'Ana', 'last_name' => 'Van Der Berg']);

    expect($user->short_name)->toBe('A. Van Der Berg');
});

// ---------------------------------------------------------------------------
// initials attribute
// ---------------------------------------------------------------------------

test('initials returns em dash when both names are empty', function () {
    $user = User::factory()->make(['first_name' => '', 'last_name' => '']);

    expect($user->initials)->toBe('—');
});

test('initials returns single uppercase letter for a single-word name', function () {
    $user = User::factory()->make(['first_name' => 'Marko', 'last_name' => '']);

    expect($user->initials)->toBe('M');
});

test('initials returns two uppercase letters for a two-word name', function () {
    $user = User::factory()->make(['first_name' => 'Ana', 'last_name' => 'Horvat']);

    expect($user->initials)->toBe('AH');
});

test('initials takes only first two parts when name has more words', function () {
    $user = User::factory()->make(['first_name' => 'Ana Maria', 'last_name' => 'Horvat']);

    expect($user->initials)->toBe('AM');
});

// ---------------------------------------------------------------------------
// canAccessPanel
// ---------------------------------------------------------------------------

test('admin user can access admin panel', function () {
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('admin');

    expect($admin->canAccessPanel($panel))->toBeTrue();
});

test('player cannot access admin panel', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('admin');

    expect($player->canAccessPanel($panel))->toBeFalse();
});

test('any user can access non-admin panels', function () {
    $user = User::factory()->create();

    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getId')->andReturn('other');

    expect($user->canAccessPanel($panel))->toBeTrue();
});

// ---------------------------------------------------------------------------
// getFallbackMediaUrl
// ---------------------------------------------------------------------------

test('getFallbackMediaUrl returns empty string for non-avatar collection', function () {
    $user = User::factory()->create();

    expect($user->getFallbackMediaUrl('default'))->toBe('');
    expect($user->getFallbackMediaUrl('photo'))->toBe('');
});

test('getFallbackMediaUrl returns placeholder svg for avatar collection', function () {
    $user = User::factory()->create();

    expect($user->getFallbackMediaUrl('avatar'))->toBe(asset('images/placeholder-avatar.svg'));
});
