<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Round;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function actingAsRoundsAdmin(): User
{
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $user = User::factory()->create();
    $user->assignRole(RoleName::Admin->value);

    return $user;
}

test('rounds page loads', function () {
    $this->withoutVite();

    $response = $this->get('/rounds');

    $response->assertSuccessful();
    $response->assertSee('Runde');
});

test('rounds page shows create round button when current event has no rounds', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    Event::factory()->create([
        'name' => 'Current Event',
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    Livewire::test('rounds-list')
        ->assertSee('Započni rundu')
        ->assertDontSee('Završi rundu');
});

test('rounds page shows finish round button when current event already has rounds', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'name' => 'Current Event',
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    Livewire::test('rounds-list')
        ->assertSee('Završi rundu')
        ->assertDontSee('Započni rundu');
});

test('admin can see round edit action and delete a round', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    Livewire::test('rounds-list')
        ->assertSee(route('filament.admin.resources.rounds.edit', ['record' => $round->id]), false)
        ->call('confirmDelete', $round->id)
        ->assertSet('confirmingDeletionId', $round->id)
        ->call('deleteRound', $round->id);

    expect(Round::query()->whereKey($round->id)->exists())->toBeFalse();
});

test('non-admin cannot delete round through livewire list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    Livewire::test('rounds-list')
        ->assertDontSee('Uredi')
        ->call('deleteRound', $round->id)
        ->assertForbidden();

    expect(Round::query()->whereKey($round->id)->exists())->toBeTrue();
});
