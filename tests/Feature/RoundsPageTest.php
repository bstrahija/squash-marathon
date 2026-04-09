<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Group;
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

test('admin can access rounds create page', function () {
    $this->withoutVite();

    $admin = actingAsRoundsAdmin();

    $response = $this->actingAs($admin)->get('/rounds/create');

    $response->assertSuccessful();
    $response->assertSee('Kreiranje runde');
});

test('non-admin cannot access rounds create page', function () {
    $this->withoutVite();

    $response = $this->actingAs(User::factory()->create())->get('/rounds/create');

    $response->assertForbidden();
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

test('rounds create livewire creates a new active round and assigns players to two groups', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $existingRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Grupa 1',
        'is_active' => true,
    ]);

    $players = User::factory()->count(4)->create();
    $event->users()->attach($players->pluck('id')->all());

    Livewire::test('rounds-create')
        ->set('groupOnePlayerIds', [$players[0]->id, $players[1]->id])
        ->set('groupTwoPlayerIds', [$players[2]->id, $players[3]->id])
        ->call('saveRound')
        ->assertRedirect(route('rounds.index'));

    $newRound = Round::query()
        ->where('event_id', $event->id)
        ->where('number', 2)
        ->first();

    expect($newRound)->not->toBeNull();
    expect($newRound?->name)->toBe('Grupa 2');
    expect($newRound?->is_active)->toBeTrue();
    expect($existingRound->fresh()->is_active)->toBeFalse();

    $groupOne = Group::query()->where('round_id', $newRound->id)->where('number', 1)->first();
    $groupTwo = Group::query()->where('round_id', $newRound->id)->where('number', 2)->first();

    expect($groupOne)->not->toBeNull();
    expect($groupTwo)->not->toBeNull();

    $groupOnePlayerIds = $groupOne->users()->pluck('users.id')->sort()->values()->all();
    $groupTwoPlayerIds = $groupTwo->users()->pluck('users.id')->sort()->values()->all();

    expect($groupOnePlayerIds)->toBe([$players[0]->id, $players[1]->id]);
    expect($groupTwoPlayerIds)->toBe([$players[2]->id, $players[3]->id]);

    $roundUserIds = $newRound->users()->pluck('users.id')->sort()->values()->all();

    expect($roundUserIds)->toBe($players->pluck('id')->sort()->values()->all());
});

test('rounds create livewire picker adds and removes players while keeping groups exclusive', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $players = User::factory()->count(3)->create();
    $event->users()->attach($players->pluck('id')->all());

    $component = Livewire::test('rounds-create')
        ->set('groupOnePlayerToAdd', $players[0]->id)
        ->assertSet('groupOnePlayerIds', [$players[0]->id])
        ->set('groupTwoPlayerToAdd', $players[0]->id)
        ->assertSet('groupTwoPlayerIds', [])
        ->set('groupTwoPlayerToAdd', $players[1]->id)
        ->assertSet('groupTwoPlayerIds', [$players[1]->id])
        ->call('removePlayerFromGroup', 1, $players[0]->id)
        ->assertSet('groupOnePlayerIds', []);

    $availablePlayerIds = $component->instance()->availablePlayers()->pluck('id')->all();

    expect($availablePlayerIds)->toContain($players[0]->id);
    expect($availablePlayerIds)->not->toContain($players[1]->id);
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
