<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Set;
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
    expect(fn () => Set::factory()->create([
        'player_one_score' => 10,
        'player_two_score' => 9,
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects set scores without a two-point lead', function () {
    expect(fn () => Set::factory()->create([
        'player_one_score' => 11,
        'player_two_score' => 10,
    ]))->toThrow(InvalidArgumentException::class);
});

it('accepts standard and extended set scores', function () {
    $standardSet = Set::factory()->create([
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    $extendedSet = Set::factory()->create([
        'player_one_score' => 12,
        'player_two_score' => 10,
    ]);

    expect($standardSet->player_one_score)->toBe(11);
    expect($extendedSet->player_one_score)->toBe(12);
});

it('determines the winner from the set score', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $set = Set::factory()->create([
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
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
    $set = Set::factory()->create([
        'player_one_score' => null,
        'player_two_score' => null,
    ]);

    expect($set->player_one_score)->toBeNull();
    expect($set->player_two_score)->toBeNull();
});

it('stores started and finished timestamps and calculates duration', function () {
    $startedAt = CarbonImmutable::parse('2026-04-07 10:00:00');
    $finishedAt = CarbonImmutable::parse('2026-04-07 10:19:30');

    $game = Game::factory()->create([
        'started_at' => $startedAt,
        'finished_at' => $finishedAt,
    ]);

    expect($game->started_at?->toDateTimeString())->toBe('2026-04-07 10:00:00');
    expect($game->finished_at?->toDateTimeString())->toBe('2026-04-07 10:19:30');
    expect($game->duration_seconds)->toBe(1170);
});
