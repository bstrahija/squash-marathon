<?php

use App\Enums\RoleName;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Livewire\Livewire;

function actingAsAdmin(): User
{
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
