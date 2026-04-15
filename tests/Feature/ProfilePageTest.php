<?php

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

test('profile page shows requested fields', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertSuccessful();
    $response->assertSee('Ime');
    $response->assertSee('Prezime');
    $response->assertSee('Nova lozinka');
    $response->assertSee('Potvrda lozinke');
    $response->assertDontSee('name="email"', false);
});

test('user can update first and last name without changing email or password', function () {
    $this->withoutVite();

    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'old@example.com',
    ]);

    $originalPasswordHash = $user->password;

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'first_name' => 'New',
        'last_name' => 'Surname',
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHas('status', 'Profil je uspješno ažuriran.');

    $user->refresh();

    expect($user->first_name)->toBe('New');
    expect($user->last_name)->toBe('Surname');
    expect($user->email)->toBe('old@example.com');
    expect($user->password)->toBe($originalPasswordHash);
});

test('user can update password with confirmation', function () {
    $this->withoutVite();

    $user = User::factory()->create([
        'email' => 'password@example.com',
    ]);

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertRedirect(route('profile'));

    $user->refresh();

    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
});

test('user cannot update password shorter than eight characters', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    $originalPasswordHash = $user->password;

    $response = $this->actingAs($user)
        ->from(route('profile'))
        ->put(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHasErrors(['password']);

    $user->refresh();

    expect($user->password)->toBe($originalPasswordHash);
});

test('user cannot change email or role through profile update', function () {
    $this->withoutVite();

    Role::findOrCreate(RoleName::Player->value);
    Role::findOrCreate(RoleName::Admin->value);

    $user = User::factory()->create([
        'first_name' => 'Player',
        'last_name' => 'One',
        'email' => 'player@example.com',
    ])->assignRole(RoleName::Player->value);

    $response = $this->actingAs($user)
        ->from(route('profile'))
        ->put(route('profile.update'), [
            'first_name' => 'Changed',
            'last_name' => 'Name',
            'email' => 'hacker@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => RoleName::Admin->value,
        ]);

    $response->assertRedirect(route('profile'));
    $response->assertSessionHasErrors(['email', 'role']);

    $user->refresh();

    expect($user->email)->toBe('player@example.com');
    expect($user->first_name)->toBe('Player');
    expect($user->hasRole(RoleName::Player->value))->toBeTrue();
    expect($user->hasRole(RoleName::Admin->value))->toBeFalse();
});
