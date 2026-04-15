<?php

use App\Enums\RoleName;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('filament.admin.pages.dashboard'));
    $response->assertRedirect(route('filament.admin.auth.login', absolute: false));
});

test('authenticated users can visit the dashboard', function () {
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    $this->actingAs($user);

    $response = $this->get(route('filament.admin.pages.dashboard'));
    $response->assertOk();
});
