<?php

use App\Enums\RoleName;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Games\Pages\CreateGame;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function actingAsAdmin(): User
{
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    return $user;
}

test('filament event create page loads', function () {
    $this->actingAs(actingAsAdmin());

    Livewire::test(CreateEvent::class)->assertOk();
});

test('filament user create page loads', function () {
    $this->actingAs(actingAsAdmin());

    Livewire::test(CreateUser::class)->assertOk();
});

test('filament game create page loads', function () {
    $this->actingAs(actingAsAdmin());

    Livewire::test(CreateGame::class)->assertOk();
});
