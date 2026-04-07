<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\GameLog;
use Livewire\Livewire;

test('game log can be created with expected structure', function () {
    $game = Game::factory()->create();

    $history = GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 1,
        'type' => GameLogType::Score,
        'side' => GameLogSide::Left,
        'player_one_score' => 1,
        'player_two_score' => 0,
        'player_one_sets' => 0,
        'player_two_sets' => 0,
    ]);

    expect($history->game_id)->toBe($game->id);
    expect($history->player_one_id)->toBe($game->player_one_id);
    expect($history->player_two_id)->toBe($game->player_two_id);
    expect($history->type)->toBe(GameLogType::Score);
    expect($history->side)->toBe(GameLogSide::Left);
    expect($history->game->id)->toBe($game->id);
});

test('game log sequence is unique per game', function () {
    $game = Game::factory()->create();

    GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 1,
    ]);

    expect(fn () => GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 1,
    ]))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

test('matches score component shows score snapshots from game log', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
    ]);

    GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 1,
        'type' => GameLogType::Score,
        'side' => GameLogSide::Left,
        'player_one_score' => 1,
        'player_two_score' => 0,
    ]);

    GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 2,
        'type' => GameLogType::Score,
        'side' => GameLogSide::Right,
        'player_one_score' => 1,
        'player_two_score' => 1,
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSee('1 - 0')
        ->assertSee('1 - 1');
});

test('matches score component score actions create game logs and update score', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('playerOneScore', 0)
        ->assertSet('playerTwoScore', 0)
        ->call('awardLeftPoint')
        ->assertSet('playerOneScore', 1)
        ->assertSet('playerTwoScore', 0)
        ->call('awardRightPoint')
        ->assertSet('playerOneScore', 1)
        ->assertSet('playerTwoScore', 1)
        ->assertSee('1 - 0')
        ->assertSee('1 - 1');

    $logs = GameLog::query()->where('game_id', $game->id)->orderBy('sequence')->get();

    expect($logs)->toHaveCount(2);
    expect($logs[0]->type)->toBe(GameLogType::Score);
    expect($logs[0]->side)->toBe(GameLogSide::Left);
    expect($logs[0]->player_one_score)->toBe(1);
    expect($logs[0]->player_two_score)->toBe(0);
    expect($logs[1]->type)->toBe(GameLogType::Score);
    expect($logs[1]->side)->toBe(GameLogSide::Right);
    expect($logs[1]->player_one_score)->toBe(1);
    expect($logs[1]->player_two_score)->toBe(1);
});

test('matches score component undo removes latest log and rolls back score', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
    ]);

    GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 1,
        'type' => GameLogType::Score,
        'side' => GameLogSide::Left,
        'player_one_score' => 1,
        'player_two_score' => 0,
    ]);

    GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 2,
        'type' => GameLogType::Score,
        'side' => GameLogSide::Right,
        'player_one_score' => 1,
        'player_two_score' => 1,
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('playerOneScore', 1)
        ->assertSet('playerTwoScore', 1)
        ->call('undoLastLog')
        ->assertSet('playerOneScore', 1)
        ->assertSet('playerTwoScore', 0)
        ->assertSet('historyScores', ['1 - 0']);

    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(1);
});
