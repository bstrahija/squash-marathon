<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\Set;
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

    Set::factory()->create([
        'game_id' => $game->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    Set::factory()->create([
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

test('matches page loads', function () {
    $this->withoutVite();

    $response = $this->get('/matches');

    $response->assertSuccessful();
    $response->assertSee('Lista svih mečeva');
});

test('matches page includes links to score page for each match', function () {
    $this->withoutVite();

    $game = createMatchForList();

    $response = $this->get('/matches');

    $response->assertSuccessful();
    $response->assertSee(route('matches.score', ['game' => $game->id]), false);
});

test('matches create page loads', function () {
    $this->withoutVite();

    $response = $this->get('/matches/create');

    $response->assertSuccessful();
});

test('matches score page loads', function () {
    $this->withoutVite();

    $game = createMatchForList();

    $response = $this->get("/matches/{$game->id}/score");

    $response->assertSuccessful();
});

test('matches score livewire starts match and closes overlay', function () {
    $game = createMatchForList();

    expect($game->started_at)->toBeNull();

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('showStartOverlay', true)
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
    ]);

    $activeRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 2,
        'name' => 'Round 2',
    ]);

    $firstRoundGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $firstRound->id,
        'number' => 1,
        'name' => 'Group A',
    ]);

    $activeRoundGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number' => 1,
        'name' => 'Group B',
    ]);

    $component = Livewire::test('matches-create');

    $groupOptions = $component->instance()->groupOptions();

    expect($groupOptions)->toHaveKey($activeRoundGroup->id);
    expect($groupOptions[$activeRoundGroup->id])->toBe('Round 2 - Group B');
    expect($groupOptions)->not->toHaveKey($firstRoundGroup->id);
    $component->assertSet('groupId', $activeRoundGroup->id);
});

test('admin can delete match through livewire list', function () {
    $game = createMatchForList();

    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin);

    Livewire::test('matches-list')
        ->assertSee('Uredi')
        ->call('confirmDelete', $game->id)
        ->assertSet('confirmingDeletionId', $game->id)
        ->call('deleteMatch', $game->id);

    expect(Game::query()->whereKey($game->id)->exists())->toBeFalse();
    expect(Set::query()->where('game_id', $game->id)->exists())->toBeFalse();
});

test('admin cannot delete match without confirmation first', function () {
    $game = createMatchForList();

    Role::firstOrCreate(['name' => RoleName::Admin->value]);

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

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
