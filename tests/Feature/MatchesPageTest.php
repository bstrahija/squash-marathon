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
