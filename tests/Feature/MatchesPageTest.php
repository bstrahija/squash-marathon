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

function createMatchForList(): Game
{
    $event = Event::factory()->create();
    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 1,
        'name' => 'Group 1',
    ]);

    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'best_of' => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    return $game;
}

function createAdminUser(): User
{
    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    return $admin;
}

function createPlayerUser(): User
{
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    return $player;
}

test('matches page loads', function () {
    $this->withoutVite();

    $response = $this->get('/matches');

    $response->assertSuccessful();
    $response->assertSee('Mečevi');
    $response->assertDontSee('Dodaj');
});

test('matches page shows add match button for admins', function () {
    $this->withoutVite();

    $admin = createAdminUser();

    $response = $this->actingAs($admin)->get('/matches');

    $response->assertSuccessful();
    $response->assertSee('Dodaj');
});

test('matches page shows add match button for players', function () {
    $this->withoutVite();

    $player = createPlayerUser();

    $response = $this->actingAs($player)->get('/matches');

    $response->assertSuccessful();
    $response->assertSee('Dodaj');
});

test('matches page includes links to score page for each match', function () {
    $this->withoutVite();

    $game = createMatchForList();

    $response = $this->get('/matches');

    $response->assertSuccessful();
    $response->assertSee(route('matches.score', ['game' => $game->id]), false);
});

test('admin can access matches create page', function () {
    $this->withoutVite();

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    Round::factory()->create([
        'event_id' => $event->id,
        'is_active' => true,
    ]);

    $admin = createAdminUser();

    $response = $this->actingAs($admin)->get('/matches/create');

    $response->assertSuccessful();
});

test('player can access matches create page', function () {
    $this->withoutVite();

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    Round::factory()->create([
        'event_id' => $event->id,
        'is_active' => true,
    ]);

    $player = createPlayerUser();

    $response = $this->actingAs($player)->get('/matches/create');

    $response->assertSuccessful();
});

test('admin is redirected to create round when creating match without active round', function () {
    $this->withoutVite();

    Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $admin = createAdminUser();

    $response = $this->actingAs($admin)->get('/matches/create');

    $response->assertRedirect(route('rounds.create', ['redirect' => 'matches.create']));
});

test('user without player/admin role cannot access matches create page', function () {
    $this->withoutVite();

    $response = $this->actingAs(User::factory()->create())->get('/matches/create');

    $response->assertForbidden();
});

test('admin can access matches score page', function () {
    $this->withoutVite();

    $game = createMatchForList();
    $admin = createAdminUser();

    $response = $this->actingAs($admin)->get("/matches/{$game->id}/score");

    $response->assertSuccessful();
});

test('player can access matches score page', function () {
    $this->withoutVite();

    $game = createMatchForList();
    $player = createPlayerUser();

    $response = $this->actingAs($player)->get("/matches/{$game->id}/score");

    $response->assertSuccessful();
});

test('user without player/admin role cannot access matches score page', function () {
    $this->withoutVite();

    $game = createMatchForList();

    $response = $this->actingAs(User::factory()->create())->get("/matches/{$game->id}/score");

    $response->assertForbidden();
});

test('player is redirected to matches page when there is no active round', function () {
    $this->withoutVite();

    Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at' => now()->addHour(),
    ]);

    $player = createPlayerUser();

    $response = $this->actingAs($player)->get('/matches/create');

    $response->assertRedirect(route('matches.index'));
});

test('matches score livewire starts match and closes overlay', function () {
    $game = createMatchForList();
    $playerOneName = $game->playerOne->full_name;
    $playerTwoName = $game->playerTwo->full_name;

    expect($game->started_at)->toBeNull();

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('showStartOverlay', true)
        ->assertSee('Početak meča')
        ->assertSee($playerOneName)
        ->assertSee($playerTwoName)
        ->assertSee('Round 1')
        ->assertSee('Group 1')
        ->call('startMatch')
        ->assertSet('showStartOverlay', false);

    expect($game->fresh()->started_at)->not->toBeNull();
});

test('matches create livewire creates a game and redirects to score page', function () {
    $event = Event::factory()->create();
    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 1,
        'name' => 'Group 1',
    ]);

    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $group->users()->attach([$playerOne->id, $playerTwo->id]);

    $component = Livewire::test('matches-create')
        ->set('groupId', $group->id)
        ->set('playerOneId', $playerOne->id)
        ->set('playerTwoId', $playerTwo->id)
        ->call('createMatch');

    $game = Game::query()->latest('id')->first();

    expect($game)->not->toBeNull();
    expect($game->event_id)->toBe($event->id);
    expect($game->round_id)->toBe($round->id);
    expect($game->group_id)->toBe($group->id);
    expect($game->player_one_id)->toBe($playerOne->id);
    expect($game->player_two_id)->toBe($playerTwo->id);

    $component->assertRedirect(route('matches.score', ['game' => $game->id]));
});

test('matches create livewire resets selected players when group changes', function () {
    $event = Event::factory()->create();
    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 1,
        'name' => 'Group 1',
    ]);

    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 2,
        'name' => 'Group 2',
    ]);

    $groupOnePlayers = User::factory()->count(2)->create();
    $groupOne->users()->attach($groupOnePlayers->pluck('id')->all());

    Livewire::test('matches-create')
        ->set('groupId', $groupOne->id)
        ->set('playerOneId', $groupOnePlayers[0]->id)
        ->set('playerTwoId', $groupOnePlayers[1]->id)
        ->set('groupId', $groupTwo->id)
        ->assertSet('playerOneId', null)
        ->assertSet('playerTwoId', null);
});

test('matches create livewire validates that selected players belong to selected group', function () {
    $event = Event::factory()->create();
    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    $selectedGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 1,
        'name' => 'Group 1',
    ]);

    $otherGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 2,
        'name' => 'Group 2',
    ]);

    $outsidePlayers = User::factory()->count(2)->create();
    $otherGroup->users()->attach($outsidePlayers->pluck('id')->all());

    Livewire::test('matches-create')
        ->set('groupId', $selectedGroup->id)
        ->set('playerOneId', $outsidePlayers[0]->id)
        ->set('playerTwoId', $outsidePlayers[1]->id)
        ->call('createMatch')
        ->assertHasErrors([
            'playerOneId' => ['exists'],
            'playerTwoId' => ['exists'],
        ]);
});

test('matches create livewire shows only active round groups with round names', function () {
    $event = Event::factory()->create();

    $firstRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
        'is_active' => true,
    ]);

    $inactiveLatestRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 2,
        'name' => 'Round 2',
        'is_active' => false,
    ]);

    $firstRoundGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $firstRound->id,
        'number' => 1,
        'name' => 'Group A',
    ]);

    $activeRoundGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $inactiveLatestRound->id,
        'number' => 1,
        'name' => 'Group B',
    ]);

    $component = Livewire::test('matches-create');

    $groupOptions = $component->instance()->groupOptions();

    expect($groupOptions)->toHaveKey($firstRoundGroup->id);
    expect($groupOptions[$firstRoundGroup->id])->toBe('Round 1 - Group A');
    expect($groupOptions)->not->toHaveKey($activeRoundGroup->id);
    $component->assertSet('groupId', $firstRoundGroup->id);
});

test('matches list shows requested column order', function () {
    $game = createMatchForList();

    $admin = createAdminUser();

    $this->actingAs($admin);

    Livewire::test('matches-list')
        ->assertSee(route('matches.score', ['game' => $game->id]), false)
        ->assertSeeInOrder(['Setovi', 'Vrijeme', 'Trajanje', 'Grupa', 'Status', 'Akcije']);
});

test('matches list can filter by player and round', function () {
    $event = Event::factory()->create();

    $roundOne = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);
    $roundTwo = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 2,
        'name' => 'Round 2',
    ]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $roundOne->id,
        'number' => 1,
        'name' => 'Group 1',
    ]);
    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $roundTwo->id,
        'number' => 2,
        'name' => 'Group 2',
    ]);

    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $playerThree = User::factory()->create();

    $gameOne = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $roundOne->id,
        'group_id' => $groupOne->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    $gameTwo = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $roundTwo->id,
        'group_id' => $groupTwo->id,
        'player_one_id' => $playerTwo->id,
        'player_two_id' => $playerThree->id,
    ]);

    $component = Livewire::test('matches-list');

    $component
        ->set('playerFilter', (string) $playerOne->id)
        ->assertSee(route('matches.score', ['game' => $gameOne->id]), false)
        ->assertDontSee(route('matches.score', ['game' => $gameTwo->id]), false);

    $component
        ->set('playerFilter', '')
        ->set('roundFilter', (string) $roundTwo->id)
        ->assertSee(route('matches.score', ['game' => $gameTwo->id]), false)
        ->assertDontSee(route('matches.score', ['game' => $gameOne->id]), false);
});

test('matches list can sort by time, status, and duration', function () {
    $event = Event::factory()->create();

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Round 1',
    ]);

    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number' => 1,
        'name' => 'Group 1',
    ]);

    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $playerThree = User::factory()->create();

    $finishedGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'created_at' => now()->subMinutes(20),
        'updated_at' => now()->subMinutes(20),
        'started_at' => now()->subMinutes(30),
        'finished_at' => now()->subMinutes(10),
        'duration_seconds' => 300,
    ]);

    $liveGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerThree->id,
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
        'started_at' => now()->subMinutes(8),
        'finished_at' => null,
        'duration_seconds' => 120,
    ]);

    $waitingGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerTwo->id,
        'player_two_id' => $playerThree->id,
        'created_at' => now()->subMinutes(1),
        'updated_at' => now()->subMinutes(1),
        'started_at' => null,
        'finished_at' => null,
        'duration_seconds' => null,
    ]);

    Livewire::test('matches-list')
        ->assertSeeInOrder([
            'matches-list-game-'.$waitingGame->id,
            'matches-list-game-'.$liveGame->id,
            'matches-list-game-'.$finishedGame->id,
        ], false)
        ->call('sortByColumn', 'status')
        ->assertSeeInOrder([
            'matches-list-game-'.$finishedGame->id,
            'matches-list-game-'.$liveGame->id,
            'matches-list-game-'.$waitingGame->id,
        ], false)
        ->call('sortByColumn', 'duration')
        ->assertSeeInOrder([
            'matches-list-game-'.$finishedGame->id,
            'matches-list-game-'.$liveGame->id,
            'matches-list-game-'.$waitingGame->id,
        ], false);
});

test('matches list shows mobile filters trigger', function () {
    Livewire::test('matches-list')
        ->assertSee('Filteri');
});

test('admin can delete match through livewire list', function () {
    $game = createMatchForList();

    $admin = createAdminUser();

    $this->actingAs($admin);

    Livewire::test('matches-list')
        ->assertSee('Uredi')
        ->call('confirmDelete', $game->id)
        ->assertSet('confirmingDeletionId', $game->id)
        ->call('deleteMatch', $game->id);

    expect(Game::query()->whereKey($game->id)->exists())->toBeFalse();
    expect(GameSet::query()->where('game_id', $game->id)->exists())->toBeFalse();
});

test('player can manage matches but cannot delete through livewire list', function () {
    $game = createMatchForList();

    $player = createPlayerUser();

    $this->actingAs($player);

    Livewire::test('matches-list')
        ->assertSee('Uredi')
        ->assertDontSee('Obriši')
        ->call('deleteMatch', $game->id)
        ->assertForbidden();

    expect(Game::query()->whereKey($game->id)->exists())->toBeTrue();
});

test('admin cannot delete match without confirmation first', function () {
    $game = createMatchForList();

    $admin = createAdminUser();

    $this->actingAs($admin);

    Livewire::test('matches-list')
        ->call('deleteMatch', $game->id)
        ->assertSet('confirmingDeletionId', null);

    expect(Game::query()->whereKey($game->id)->exists())->toBeTrue();
});

test('non-admin cannot delete match through livewire list', function () {
    $game = createMatchForList();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('matches-list')
        ->assertDontSee('Uredi')
        ->call('deleteMatch', $game->id)
        ->assertForbidden();

    expect(Game::query()->whereKey($game->id)->exists())->toBeTrue();
});
