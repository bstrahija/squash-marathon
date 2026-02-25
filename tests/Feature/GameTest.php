<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Set;
use App\Models\User;

it('creates a game with players and event', function () {
    $game = Game::factory()->create();

    expect($game->event)->toBeInstanceOf(Event::class);
    expect($game->playerOne)->toBeInstanceOf(User::class);
    expect($game->playerTwo)->toBeInstanceOf(User::class);
});

it('rejects invalid best_of values', function () {
    expect(fn () => Game::factory()->create([
        'best_of' => 2,
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

it('determines the match winner from set scores', function () {
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $winnerId = Game::determineWinnerIdFromSetScores([
        ['player_one_score' => 11, 'player_two_score' => 7],
        ['player_one_score' => 9, 'player_two_score' => 11],
        ['player_one_score' => 11, 'player_two_score' => 5],
    ], 3, $playerOne->id, $playerTwo->id);

    expect($winnerId)->toBe($playerOne->id);
});

it('allows sets without scores', function () {
    $set = Set::factory()->create([
        'player_one_score' => null,
        'player_two_score' => null,
    ]);

    expect($set->player_one_score)->toBeNull();
    expect($set->player_two_score)->toBeNull();
});
