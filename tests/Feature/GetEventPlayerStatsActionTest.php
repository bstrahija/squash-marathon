<?php

use App\Actions\GetEventPlayerStatsAction;
use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeCompletedGame(Event $event, User $winner, User $loser): Game
{
    $roundNumber = Round::nextNumberForEvent($event->id);
    $round       = Round::factory()->create(['event_id' => $event->id, 'number' => $roundNumber]);
    $group       = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1]);

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'player_one_id' => $winner->id,
        'player_two_id' => $loser->id,
        'best_of'       => 2,
    ]);

    // Two sets won by player one — best_of=2 means exactly 2 sets needed
    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $winner->id,
        'player_two_id'    => $loser->id,
        'player_one_score' => 11,
        'player_two_score' => 5,
    ]);
    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $winner->id,
        'player_two_id'    => $loser->id,
        'player_one_score' => 11,
        'player_two_score' => 3,
    ]);

    return $game;
}

// ---------------------------------------------------------------------------
// Basic stats
// ---------------------------------------------------------------------------

test('action counts wins draws losses and games for each player', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event = Event::factory()->create();

    $playerA = User::factory()->create();
    $playerB = User::factory()->create();

    $event->users()->sync([$playerA->id, $playerB->id]);
    $playerA->assignRole(RoleName::Player->value);
    $playerB->assignRole(RoleName::Player->value);

    makeCompletedGame($event, $playerA, $playerB);

    $stats = (new GetEventPlayerStatsAction)->execute($event);

    $statsA = $stats->get($playerA->id);
    $statsB = $stats->get($playerB->id);

    expect($statsA['wins'])->toBe(1);
    expect($statsA['losses'])->toBe(0);
    expect($statsA['draws'])->toBe(0);
    expect($statsA['games'])->toBe(1);

    expect($statsB['wins'])->toBe(0);
    expect($statsB['losses'])->toBe(1);
    expect($statsB['draws'])->toBe(0);
    expect($statsB['games'])->toBe(1);
});

test('action skips incomplete games', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event = Event::factory()->create();

    $playerA = User::factory()->create();
    $playerB = User::factory()->create();

    $event->users()->sync([$playerA->id, $playerB->id]);
    $playerA->assignRole(RoleName::Player->value);
    $playerB->assignRole(RoleName::Player->value);

    // Game with no sets — incomplete
    Game::factory()->create([
        'event_id'      => $event->id,
        'player_one_id' => $playerA->id,
        'player_two_id' => $playerB->id,
        'best_of'       => 2,
    ]);

    $stats = (new GetEventPlayerStatsAction)->execute($event);

    expect($stats->get($playerA->id)['games'])->toBe(0);
    expect($stats->get($playerB->id)['games'])->toBe(0);
});

test('last game at is set to the most recent completed game created at', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event = Event::factory()->create();

    $playerA = User::factory()->create();
    $playerB = User::factory()->create();

    $event->users()->sync([$playerA->id, $playerB->id]);
    $playerA->assignRole(RoleName::Player->value);
    $playerB->assignRole(RoleName::Player->value);

    $game1 = makeCompletedGame($event, $playerA, $playerB);
    $game1->update(['created_at' => now()->subHour()]);

    $game2 = makeCompletedGame($event, $playerA, $playerB);
    $game2->update(['created_at' => now()]);

    $stats = (new GetEventPlayerStatsAction)->execute($event);

    expect($stats->get($playerA->id)['last_game_at']->toDateTimeString())
        ->toBe($game2->fresh()->created_at->toDateTimeString());
});

test('event with no games returns all players with zero stats', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event = Event::factory()->create();

    $players = User::factory()->count(3)->create();
    $event->users()->sync($players->pluck('id'));
    $players->each(fn ($p) => $p->assignRole(RoleName::Player->value));

    $stats = (new GetEventPlayerStatsAction)->execute($event);

    expect($stats)->toHaveCount(3);
    $stats->each(function (array $row) {
        expect($row['wins'])->toBe(0);
        expect($row['losses'])->toBe(0);
        expect($row['draws'])->toBe(0);
        expect($row['games'])->toBe(0);
        expect($row['last_game_at'])->toBeNull();
    });
});

test('player not in event stats map is skipped gracefully', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event = Event::factory()->create();

    $playerA  = User::factory()->create();
    $outsider = User::factory()->create();

    // Only playerA is in the event
    $event->users()->sync([$playerA->id]);
    $playerA->assignRole(RoleName::Player->value);
    $outsider->assignRole(RoleName::Player->value);

    // Game involves outsider who is not in the event's participant list
    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'player_one_id' => $playerA->id,
        'player_two_id' => $outsider->id,
        'best_of'       => 2,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerA->id,
        'player_two_id'    => $outsider->id,
        'player_one_score' => 11,
        'player_two_score' => 5,
    ]);
    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerA->id,
        'player_two_id'    => $outsider->id,
        'player_one_score' => 11,
        'player_two_score' => 3,
    ]);

    $stats = (new GetEventPlayerStatsAction)->execute($event);

    // outsider should not appear in stats
    expect($stats->has($outsider->id))->toBeFalse();

    // playerA's game count still incremented for the completed game
    expect($stats->get($playerA->id)['games'])->toBe(1);
});
