<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
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

function createCompletedGroupGame(
    Event $event,
    Round $round,
    Group $group,
    User $playerOne,
    User $playerTwo,
    int $playerOneScore,
    int $playerTwoScore,
): Game {
    $winnerId = $playerOneScore > $playerTwoScore ? $playerOne->id : $playerTwo->id;

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'best_of' => 1,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'winner_id' => $winnerId,
        'is_draw' => false,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => $playerOneScore,
        'player_two_score' => $playerTwoScore,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
    ]);

    return $game;
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
    $response->assertSee('Kreiranje prve runde');
});

test('non-admin cannot access rounds create page', function () {
    $this->withoutVite();

    $response = $this->actingAs(User::factory()->create())->get('/rounds/create');

    $response->assertForbidden();
});

test('admin can access rounds edit page', function () {
    $this->withoutVite();

    $admin = actingAsRoundsAdmin();

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $player = User::factory()->create();
    $event->users()->attach($player->id);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $response = $this->actingAs($admin)->get("/rounds/{$round->id}/edit");

    $response->assertSuccessful();
    $response->assertSee('Uređivanje runde');
});

test('non-admin cannot access rounds edit page', function () {
    $this->withoutVite();

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $response = $this->actingAs(User::factory()->create())->get("/rounds/{$round->id}/edit");

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
        ->assertSee(route('rounds.create', ['mode' => 'start']), false)
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
        ->assertSee(route('rounds.create', ['mode' => 'finish']), false)
        ->assertDontSee('Započni rundu');
});

test('rounds create page shows finish headline when mode is finish', function () {
    $this->withoutVite();

    $admin = actingAsRoundsAdmin();

    $response = $this->actingAs($admin)->get('/rounds/create?mode=finish');

    $response->assertSuccessful();
    $response->assertSee('Završi rundu i kreiraj novu');
});

test('rounds create livewire exposes previous-round points seeding only when previous round exists', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $withoutPreviousRound = Livewire::test('rounds-create');

    expect($withoutPreviousRound->instance()->hasPreviousRound)->toBeFalse();

    Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Runda 1',
    ]);

    $withPreviousRound = Livewire::test('rounds-create');

    expect($withPreviousRound->instance()->hasPreviousRound)->toBeTrue();
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
        ->assertSee(route('rounds.edit', ['round' => $round->id]), false)
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
    expect($newRound?->name)->toBe('Runda 2');
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

test('rounds create livewire can randomly seed players into two balanced groups', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $players = User::factory()->count(5)->create();
    $event->users()->attach($players->pluck('id')->all());

    $component = Livewire::test('rounds-create')
        ->call('seedRandomGroups');

    $groupOneIds = collect($component->instance()->groupOnePlayerIds)
        ->map(fn ($id): int => (int) $id)
        ->values();

    $groupTwoIds = collect($component->instance()->groupTwoPlayerIds)
        ->map(fn ($id): int => (int) $id)
        ->values();

    $allAssignedIds = $groupOneIds->merge($groupTwoIds)->sort()->values()->all();

    expect($groupOneIds->intersect($groupTwoIds)->all())->toBe([]);
    expect($allAssignedIds)->toBe($players->pluck('id')->sort()->values()->all());
    expect(abs($groupOneIds->count() - $groupTwoIds->count()))->toBeLessThanOrEqual(1);
});

test('rounds create livewire can seed players by previous round points', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $players = User::factory()->count(4)->create();
    $event->users()->attach($players->pluck('id')->all());

    $previousRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Runda 1',
    ]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $previousRound->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $previousRound->id,
        'number' => 2,
        'name' => 'Grupa 2',
    ]);

    $groupOne->users()->sync([$players[0]->id, $players[1]->id]);
    $groupTwo->users()->sync([$players[2]->id, $players[3]->id]);
    $previousRound->users()->sync($players->pluck('id')->all());

    createCompletedGroupGame($event, $previousRound, $groupOne, $players[0], $players[1], 11, 6);
    createCompletedGroupGame($event, $previousRound, $groupTwo, $players[2], $players[3], 11, 8);

    $component = Livewire::test('rounds-create')
        ->call('seedGroupsFromPreviousRoundPoints');

    expect($component->instance()->groupOnePlayerIds)->toBe([$players[0]->id, $players[2]->id]);
    expect($component->instance()->groupTwoPlayerIds)->toBe([$players[1]->id, $players[3]->id]);
});

test('rounds create livewire redirects back to matches create when requested', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $players = User::factory()->count(4)->create();
    $event->users()->attach($players->pluck('id')->all());

    Livewire::withQueryParams(['redirect' => 'matches.create'])
        ->test('rounds-create')
        ->set('groupOnePlayerIds', [$players[0]->id, $players[1]->id])
        ->set('groupTwoPlayerIds', [$players[2]->id, $players[3]->id])
        ->call('saveRound')
        ->assertRedirect(route('matches.create'));
});

test('rounds edit livewire updates round title and players by groups', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 2,
        'name' => 'Grupa 2',
    ]);

    $players = User::factory()->count(4)->create();
    $event->users()->attach($players->pluck('id')->all());

    $groupOne->users()->sync([$players[0]->id, $players[1]->id]);
    $groupTwo->users()->sync([$players[2]->id, $players[3]->id]);
    $round->users()->sync($players->pluck('id')->all());

    Livewire::test('rounds-edit', ['roundId' => $round->id])
        ->set('roundName', 'Grupa Finale')
        ->set('groupOnePlayerIds', [$players[1]->id, $players[2]->id])
        ->set('groupTwoPlayerIds', [$players[0]->id, $players[3]->id])
        ->call('saveRound')
        ->assertRedirect(route('rounds.index'));

    expect($round->fresh()->name)->toBe('Grupa Finale');

    $groupOnePlayerIds = $groupOne->fresh()->users()->pluck('users.id')->sort()->values()->all();
    $groupTwoPlayerIds = $groupTwo->fresh()->users()->pluck('users.id')->sort()->values()->all();

    expect($groupOnePlayerIds)->toBe([$players[1]->id, $players[2]->id]);
    expect($groupTwoPlayerIds)->toBe([$players[0]->id, $players[3]->id]);

    $roundUserIds = $round->fresh()->users()->pluck('users.id')->sort()->values()->all();

    expect($roundUserIds)->toBe($players->pluck('id')->sort()->values()->all());
});

test('rounds edit livewire exposes previous-round points seeding only when previous round exists', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $roundOne = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Runda 1',
    ]);

    $withoutPreviousRound = Livewire::test('rounds-edit', ['roundId' => $roundOne->id]);

    expect($withoutPreviousRound->instance()->hasPreviousRound)->toBeFalse();

    $roundTwo = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 2,
        'name' => 'Runda 2',
    ]);

    $withPreviousRound = Livewire::test('rounds-edit', ['roundId' => $roundTwo->id]);

    expect($withPreviousRound->instance()->hasPreviousRound)->toBeTrue();
});

test('rounds edit livewire can seed players by previous round points', function () {
    $admin = actingAsRoundsAdmin();
    $this->actingAs($admin);

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $players = User::factory()->count(4)->create();
    $event->users()->attach($players->pluck('id')->all());

    $previousRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Runda 1',
    ]);

    $currentRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 2,
        'name' => 'Runda 2',
    ]);

    $previousGroupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $previousRound->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $previousGroupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $previousRound->id,
        'number' => 2,
        'name' => 'Grupa 2',
    ]);

    Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $currentRound->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $currentRound->id,
        'number' => 2,
        'name' => 'Grupa 2',
    ]);

    $previousGroupOne->users()->sync([$players[0]->id, $players[1]->id]);
    $previousGroupTwo->users()->sync([$players[2]->id, $players[3]->id]);
    $previousRound->users()->sync($players->pluck('id')->all());

    createCompletedGroupGame($event, $previousRound, $previousGroupOne, $players[0], $players[1], 11, 7);
    createCompletedGroupGame($event, $previousRound, $previousGroupTwo, $players[2], $players[3], 11, 9);

    $component = Livewire::test('rounds-edit', ['roundId' => $currentRound->id])
        ->call('seedGroupsFromPreviousRoundPoints');

    expect($component->instance()->groupOnePlayerIds)->toBe([$players[0]->id, $players[2]->id]);
    expect($component->instance()->groupTwoPlayerIds)->toBe([$players[1]->id, $players[3]->id]);
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
