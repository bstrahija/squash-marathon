<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use Carbon\CarbonImmutable;

it('creates a game with players and event', function () {
    $game = Game::factory()->create();

    expect($game->event)->toBeInstanceOf(Event::class);
    expect($game->playerOne)->toBeInstanceOf(User::class);
    expect($game->playerTwo)->toBeInstanceOf(User::class);
});

it('accepts supported best_of values and rejects unsupported ones', function () {
    foreach ([1, 2, 3, 5] as $bestOf) {
        $game = Game::factory()->create([
            'best_of' => $bestOf,
        ]);

        expect($game->best_of)->toBe($bestOf);
    }

    expect(fn () => Game::factory()->create([
        'best_of' => 4,
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects set scores that do not reach 11', function () {
    expect(fn () => GameSet::factory()->create([
        'player_one_score' => 10,
        'player_two_score' => 9,
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects set scores without a two-point lead', function () {
    expect(fn () => GameSet::factory()->create([
        'player_one_score' => 11,
        'player_two_score' => 10,
    ]))->toThrow(InvalidArgumentException::class);
});

it('accepts standard and extended set scores', function () {
    $standardSet = GameSet::factory()->create([
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    $extendedSet = GameSet::factory()->create([
        'player_one_score' => 12,
        'player_two_score' => 10,
    ]);

    expect($standardSet->player_one_score)->toBe(11);
    expect($extendedSet->player_one_score)->toBe(12);
});

it('determines the winner from the set score', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $set = GameSet::factory()->create([
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 5,
    ]);

    expect($set->winner_id)->toBe($playerOne->id);
});

it('determines the match result from set scores', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $result = Game::determineMatchResultFromSetScores([
        ['player_one_score' => 11, 'player_two_score' => 7],
        ['player_one_score' => 8, 'player_two_score' => 11],
    ], 2, $playerOne->id, $playerTwo->id);

    expect($result['is_complete'])->toBeTrue();
    expect($result['is_draw'])->toBeTrue();
    expect($result['winner_id'])->toBeNull();
});

it('determines completion only after all sets are played for best_of 3', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $incomplete = Game::determineMatchResultFromSetScores([
        ['player_one_score' => 11, 'player_two_score' => 8],
        ['player_one_score' => 8, 'player_two_score' => 11],
    ], 3, $playerOne->id, $playerTwo->id);

    expect($incomplete['is_complete'])->toBeFalse();

    $complete = Game::determineMatchResultFromSetScores([
        ['player_one_score' => 11, 'player_two_score' => 8],
        ['player_one_score' => 8, 'player_two_score' => 11],
        ['player_one_score' => 11, 'player_two_score' => 6],
    ], 3, $playerOne->id, $playerTwo->id);

    expect($complete['is_complete'])->toBeTrue();
    expect($complete['is_draw'])->toBeFalse();
    expect($complete['winner_id'])->toBe($playerOne->id);
});

it('allows sets without scores', function () {
    $set = GameSet::factory()->create([
        'player_one_score' => null,
        'player_two_score' => null,
    ]);

    expect($set->player_one_score)->toBeNull();
    expect($set->player_two_score)->toBeNull();
});

it('stores started and finished timestamps and calculates duration', function () {
    $startedAt  = CarbonImmutable::parse('2026-04-07 10:00:00');
    $finishedAt = CarbonImmutable::parse('2026-04-07 10:19:30');

    $game = Game::factory()->create([
        'started_at'  => $startedAt,
        'finished_at' => $finishedAt,
    ]);

    expect($game->started_at?->toDateTimeString())->toBe('2026-04-07 10:00:00');
    expect($game->finished_at?->toDateTimeString())->toBe('2026-04-07 10:19:30');
    expect($game->duration_seconds)->toBe(1170);
});

it('auto-populates round_id from the group on save', function () {
    $game = Game::factory()->create();

    expect($game->round_id)->not->toBeNull();
    expect($game->round_id)->toBe($game->group->round_id);
});

it('throws when both game players are the same user', function () {
    $player = User::factory()->create();

    expect(fn () => Game::factory()->create([
        'player_one_id' => $player->id,
        'player_two_id' => $player->id,
    ]))->toThrow(InvalidArgumentException::class, 'Game players must be different.');
});

it('reports waiting state when game has not started', function () {
    $game = Game::factory()->create(['started_at' => null, 'finished_at' => null]);

    expect($game->isWaiting())->toBeTrue();
    expect($game->isLive())->toBeFalse();
    expect($game->isFinished())->toBeFalse();
});

it('reports live state when game has started but not finished', function () {
    $game = Game::factory()->create([
        'started_at'  => now()->subMinutes(10),
        'finished_at' => null,
    ]);

    expect($game->isLive())->toBeTrue();
    expect($game->isWaiting())->toBeFalse();
    expect($game->isFinished())->toBeFalse();
});

it('reports finished state when game has a finished_at timestamp', function () {
    $game = Game::factory()->create([
        'started_at'  => now()->subMinutes(30),
        'finished_at' => now()->subMinutes(5),
    ]);

    expect($game->isFinished())->toBeTrue();
    expect($game->isLive())->toBeFalse();
});

it('reports finished state via loaded sets when result is complete', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $game = Game::factory()->create([
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'started_at'    => now()->subMinutes(30),
        'finished_at'   => null,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 8,
        'player_two_score' => 11,
    ]);

    $game->load('sets');

    expect($game->isFinished())->toBeTrue();
});

it('returns a dash for score summary when no sets have scores', function () {
    $game = Game::factory()->create();
    $game->setRelation('sets', collect());

    expect($game->scoreSummary())->toBe('—');
});

it('returns formatted score summary from loaded sets', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $game = Game::factory()->create([
        'best_of'       => 3,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 4,
    ]);

    $game->load('sets');

    expect($game->scoreSummary())->toBe('11-7, 11-4');
});

it('returns set result summary in wins format', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $game = Game::factory()->create([
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 4,
        'player_two_score' => 11,
    ]);

    $game->load('sets');

    expect($game->setResultSummary())->toBe('1-1');
});

it('returns incomplete result for an invalid best_of value', function () {
    $result = Game::determineMatchResultFromSetScores([], 4, 1, 2);

    expect($result['is_complete'])->toBeFalse();
    expect($result['winner_id'])->toBeNull();
    expect($result['player_one_wins'])->toBe(0);
    expect($result['player_two_wins'])->toBe(0);
});

it('returns incomplete result when player ids are null', function () {
    $result = Game::determineMatchResultFromSetScores(
        [['player_one_score' => 11, 'player_two_score' => 7]],
        1,
        null,
        null,
    );

    expect($result['is_complete'])->toBeFalse();
});

it('skips sets with tied scores', function () {
    $result = Game::determineMatchResultFromSetScores(
        [['player_one_score' => 11, 'player_two_score' => 11]],
        1,
        1,
        2,
    );

    expect($result['is_complete'])->toBeFalse();
    expect($result['player_one_wins'])->toBe(0);
    expect($result['player_two_wins'])->toBe(0);
});

it('skips sets with an invalid extension where loser has under 10 points', function () {
    $result = Game::determineMatchResultFromSetScores(
        [['player_one_score' => 14, 'player_two_score' => 8]],
        1,
        1,
        2,
    );

    expect($result['is_complete'])->toBeFalse();
    expect($result['player_one_wins'])->toBe(0);
});

it('counts a valid extended set beyond 11 when both players reached 10', function () {
    $result = Game::determineMatchResultFromSetScores(
        [['player_one_score' => 13, 'player_two_score' => 11]],
        1,
        1,
        2,
    );

    expect($result['is_complete'])->toBeTrue();
    expect($result['winner_id'])->toBe(1);
    expect($result['player_one_wins'])->toBe(1);
});

it('declares player two as the winner when player two wins all sets', function () {
    $result = Game::determineMatchResultFromSetScores(
        [
            ['player_one_score' => 7, 'player_two_score' => 11],
            ['player_one_score' => 5, 'player_two_score' => 11],
        ],
        2,
        1,
        2,
    );

    expect($result['is_complete'])->toBeTrue();
    expect($result['is_draw'])->toBeFalse();
    expect($result['winner_id'])->toBe(2);
    expect($result['player_one_wins'])->toBe(0);
    expect($result['player_two_wins'])->toBe(2);
});

it('throws when only one set score is provided', function () {
    expect(fn () => GameSet::factory()->create([
        'player_one_score' => 11,
        'player_two_score' => null,
    ]))->toThrow(InvalidArgumentException::class, 'Set scores must be provided for both players.');

    expect(fn () => GameSet::factory()->create([
        'player_one_score' => null,
        'player_two_score' => 7,
    ]))->toThrow(InvalidArgumentException::class, 'Set scores must be provided for both players.');
});

it('throws when a set score is negative', function () {
    expect(fn () => GameSet::factory()->create([
        'player_one_score' => -1,
        'player_two_score' => 11,
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => GameSet::factory()->create([
        'player_one_score' => 11,
        'player_two_score' => -3,
    ]))->toThrow(InvalidArgumentException::class);
});

it('throws when both game set players are the same user', function () {
    $player = User::factory()->create();
    $other  = User::factory()->create();
    $game   = Game::factory()->create(['player_one_id' => $player->id, 'player_two_id' => $other->id]);

    expect(fn () => GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $player->id,
        'player_two_id'    => $player->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]))->toThrow(InvalidArgumentException::class, 'Set players must be different.');
});

it('sets player two as the winner when player two has the higher score', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $set = GameSet::factory()->create([
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 5,
        'player_two_score' => 11,
    ]);

    expect($set->winner_id)->toBe($playerTwo->id);
});

it('auto-populates round_id and group_id from game on set save', function () {
    $game = Game::factory()->create();

    $set = GameSet::factory()->create([
        'game_id'  => $game->id,
        'round_id' => null,
        'group_id' => null,
    ]);

    expect($set->round_id)->toBe($game->round_id);
    expect($set->group_id)->toBe($game->group_id);
});

it('calculates set duration from started and finished timestamps', function () {
    $startedAt  = CarbonImmutable::parse('2026-04-07 10:00:00');
    $finishedAt = CarbonImmutable::parse('2026-04-07 10:05:30');

    $set = GameSet::factory()->create([
        'started_at'  => $startedAt,
        'finished_at' => $finishedAt,
    ]);

    expect($set->duration_seconds)->toBe(330);
});
