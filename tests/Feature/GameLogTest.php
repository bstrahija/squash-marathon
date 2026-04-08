<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\GameLog;
use App\Models\Set;
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
        ->assertSee('1 - 1')
        ->assertSeeInOrder(['1 - 1', '1 - 0']);
});

test('matches score component score actions create game logs and update score', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left')
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
    expect($logs[0]->serving_player_id)->toBe($game->player_one_id);
    expect($logs[0]->serving_side)->toBe(GameLogSide::Left);
    expect($logs[0]->player_one_score)->toBe(1);
    expect($logs[0]->player_two_score)->toBe(0);
    expect($logs[1]->type)->toBe(GameLogType::Score);
    expect($logs[1]->side)->toBe(GameLogSide::Right);
    expect($logs[1]->serving_player_id)->toBe($game->player_two_id);
    expect($logs[1]->serving_side)->toBe(GameLogSide::Right);
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

test('serving controls follow expected visibility and side transitions', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('servingPlayer', null)
        ->assertSet('servingPending', true)
        ->call('selectServe', 'left')
        ->assertSet('servingPlayer', 'left')
        ->assertSet('servingSide', 'right')
        ->assertSet('servingPending', true)
        ->call('selectServe', 'left')
        ->assertSet('servingSide', 'left')
        ->assertSet('servingPending', true)
        ->call('awardLeftPoint')
        ->assertSet('servingPlayer', 'left')
        ->assertSet('servingSide', 'right')
        ->assertSet('servingPending', false)
        ->call('selectServe', 'left')
        ->assertSet('servingSide', 'left')
        ->assertSet('servingPending', false)
        ->call('awardLeftPoint')
        ->assertSet('servingPlayer', 'left')
        ->assertSet('servingSide', 'right')
        ->assertSet('servingPending', false)
        ->call('awardRightPoint')
        ->assertSet('servingPlayer', 'right')
        ->assertSet('servingSide', 'right')
        ->assertSet('servingPending', true);
});

test('clicking score before selecting serve assumes right serving side', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('servingPlayer', null)
        ->assertSet('servingSide', 'right')
        ->assertSet('servingPending', true)
        ->call('awardLeftPoint')
        ->assertSet('playerOneScore', 1)
        ->assertSet('playerTwoScore', 0)
        ->assertSet('servingPlayer', 'left')
        ->assertSet('servingSide', 'left')
        ->assertSet('servingPending', false)
        ->assertSet('historyScores', ['1 - 0']);

    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(1);
});

test('matches score component can restart game after confirmation', function () {
    $originalStartedAt = now()->subMinutes(10);

    $game = Game::factory()->create([
        'started_at' => $originalStartedAt,
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
        ->call('requestRestartGame')
        ->assertSet('showRestartConfirmation', true)
        ->call('confirmRestartGame')
        ->assertSet('showRestartConfirmation', false)
        ->assertSet('playerOneScore', 0)
        ->assertSet('playerTwoScore', 0)
        ->assertSet('historyScores', []);

    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(0);

    $refreshedGame = $game->fresh();

    expect($refreshedGame)->not->toBeNull();
    expect($refreshedGame->started_at)->not->toBeNull();
    expect($refreshedGame->started_at->gt($originalStartedAt))->toBeTrue();
});

test('set ends only when score is at least 11 with a two-point lead', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 1,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 10; $i++) {
        $component->call('awardLeftPoint');
        $component->call('awardRightPoint');
    }

    $component
        ->call('awardLeftPoint')
        ->assertSet('playerOneScore', 11)
        ->assertSet('playerTwoScore', 10)
        ->assertSet('showMatchDoneOverlay', false)
        ->call('awardRightPoint')
        ->call('awardLeftPoint')
        ->call('awardRightPoint')
        ->call('awardLeftPoint')
        ->assertSet('playerOneScore', 13)
        ->assertSet('playerTwoScore', 12)
        ->assertSet('showMatchDoneOverlay', false)
        ->call('awardLeftPoint')
        ->assertSet('playerOneScore', 14)
        ->assertSet('playerTwoScore', 12)
        ->assertSet('showMatchDoneOverlay', true)
        ->assertSet('showNextSetOverlay', false);

    $set = Set::query()->where('game_id', $game->id)->latest('id')->first();

    expect($set)->not->toBeNull();
    expect($set?->player_one_score)->toBe(14);
    expect($set?->player_two_score)->toBe(12);
    expect($set?->finished_at)->not->toBeNull();
});

test('after non-final set end next set dialog appears and next set resets live score', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 2,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showNextSetOverlay', true)
        ->assertSet('showMatchDoneOverlay', false)
        ->call('startNextSet')
        ->assertSet('showNextSetOverlay', false)
        ->assertSet('playerOneScore', 0)
        ->assertSet('playerTwoScore', 0)
        ->assertSet('historyScores', []);

    $game->refresh();

    expect(Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count())->toBe(1);
    expect(Set::query()->where('game_id', $game->id)->whereNull('finished_at')->count())->toBe(1);
    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(0);
    expect($game->player_one_sets)->toBe(1);
    expect($game->player_two_sets)->toBe(0);
    expect($game->finished_at)->toBeNull();
});

test('next set dialog undo reverts mistaken final point', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 2,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showNextSetOverlay', true)
        ->call('undoLastLog')
        ->assertSet('showNextSetOverlay', false)
        ->assertSet('playerOneScore', 10)
        ->assertSet('playerTwoScore', 0);

    $game->refresh();

    expect(Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count())->toBe(0);
    expect(Set::query()->where('game_id', $game->id)->whereNull('finished_at')->count())->toBe(1);
    expect($game->player_one_sets)->toBe(0);
    expect($game->player_two_sets)->toBe(0);
    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(10);
});

test('undoing to zero points in next set shows next set dialog and allows going back to previous set', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 2,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showNextSetOverlay', true)
        ->call('startNextSet')
        ->assertSet('showNextSetOverlay', false)
        ->call('selectServe', 'left')
        ->call('awardLeftPoint')
        ->call('awardLeftPoint')
        ->assertSet('playerOneScore', 2)
        ->call('undoLastLog')
        ->assertSet('playerOneScore', 1)
        ->assertSet('showNextSetOverlay', false)
        ->call('undoLastLog')
        ->assertSet('playerOneScore', 0)
        ->assertSet('showNextSetOverlay', true)
        ->call('undoLastLog')
        ->assertSet('showNextSetOverlay', false)
        ->assertSet('playerOneSets', 0)
        ->assertSet('playerTwoSets', 0)
        ->assertSet('playerOneScore', 10)
        ->assertSet('playerTwoScore', 0)
        ->assertSet('historyScores', ['10 - 0']);

    $game->refresh();

    expect(Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count())->toBe(0);
    expect(Set::query()->where('game_id', $game->id)->whereNull('finished_at')->count())->toBe(1);
    expect($game->player_one_sets)->toBe(0);
    expect($game->player_two_sets)->toBe(0);
    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(1);
});

test('undo immediately after starting next set returns to previous set boundary state', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 2,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showNextSetOverlay', true)
        ->call('startNextSet')
        ->assertSet('showNextSetOverlay', false)
        ->call('undoLastLog')
        ->assertSet('showNextSetOverlay', false)
        ->assertSet('playerOneSets', 0)
        ->assertSet('playerTwoSets', 0)
        ->assertSet('playerOneScore', 10)
        ->assertSet('playerTwoScore', 0)
        ->assertSet('historyScores', ['10 - 0']);

    $game->refresh();

    expect(Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count())->toBe(0);
    expect(Set::query()->where('game_id', $game->id)->whereNull('finished_at')->count())->toBe(1);
    expect($game->player_one_sets)->toBe(0);
    expect($game->player_two_sets)->toBe(0);
    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(1);
});

test('undo backs out empty current set before removing historical logs', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 2,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showNextSetOverlay', true)
        ->call('startNextSet')
        ->assertSet('showNextSetOverlay', false);

    GameLog::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'sequence' => 1,
        'type' => GameLogType::Score,
        'side' => GameLogSide::Left,
        'serving_player_id' => $game->player_one_id,
        'serving_side' => GameLogSide::Right,
        'player_one_score' => 11,
        'player_two_score' => 0,
        'player_one_sets' => 0,
        'player_two_sets' => 0,
    ]);

    $component->call('undoLastLog');

    expect(Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count())->toBe(0);
    expect(Set::query()->where('game_id', $game->id)->whereNull('finished_at')->count())->toBe(1);
    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(2);
});

test('match done dialog undo removes last point and reopens set', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 1,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showMatchDoneOverlay', true)
        ->assertSet('playerOneScore', 11)
        ->call('undoLastLog')
        ->assertSet('showMatchDoneOverlay', false)
        ->assertSet('playerOneScore', 10)
        ->assertSet('playerTwoScore', 0);

    $game->refresh();

    expect($game->finished_at)->toBeNull();
    expect($game->player_one_sets)->toBe(0);
    expect($game->player_two_sets)->toBe(0);
    expect(Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count())->toBe(0);
    expect(Set::query()->where('game_id', $game->id)->whereNull('finished_at')->count())->toBe(1);
    expect(GameLog::query()->where('game_id', $game->id)->count())->toBe(10);
});

test('match done dialog shows Croatian labels and plain per-set point badges', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 1,
    ]);

    $playerOneName = $game->playerOne->full_name;
    $playerTwoName = $game->playerTwo->full_name;

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showMatchDoneOverlay', true)
        ->assertSee('Pobjednik')
        ->assertSee($playerOneName)
        ->assertSee('Luzer')
        ->assertSee($playerTwoName)
        ->assertSee('Konačni rezultat')
        ->assertSee('1 - 0')
        ->assertDontSee('Rezultat setova')
        ->assertSee('11:0')
        ->assertDontSee('Set Durations')
        ->assertDontSee('Match Duration');
});

test('match done dialog for draw shows remi, both players, and scores', function () {
    $game = Game::factory()->create([
        'started_at' => now()->subMinutes(20),
        'finished_at' => now(),
        'best_of' => 2,
        'player_one_sets' => 1,
        'player_two_sets' => 1,
        'winner_id' => null,
        'is_draw' => true,
    ]);

    $playerOneName = $game->playerOne->full_name;
    $playerTwoName = $game->playerTwo->full_name;

    Set::factory()->create([
        'game_id' => $game->id,
        'round_id' => $game->round_id,
        'group_id' => $game->group_id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'started_at' => now()->subMinutes(19),
        'finished_at' => now()->subMinutes(15),
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'round_id' => $game->round_id,
        'group_id' => $game->group_id,
        'player_one_id' => $game->player_one_id,
        'player_two_id' => $game->player_two_id,
        'started_at' => now()->subMinutes(14),
        'finished_at' => now()->subMinutes(10),
        'player_one_score' => 8,
        'player_two_score' => 11,
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
        'player_one_sets' => 0,
        'player_two_sets' => 0,
    ]);

    Livewire::test('matches-score', ['gameId' => $game->id])
        ->assertSet('showMatchDoneOverlay', true)
        ->assertSee('Remi')
        ->assertSee($playerOneName)
        ->assertSee('VS')
        ->assertSee($playerTwoName)
        ->assertSee('Konačni rezultat')
        ->assertSee('1 - 1')
        ->assertSee('11:8')
        ->assertSee('8:11')
        ->assertDontSee('Pobjednik')
        ->assertDontSee('Luzer');
});

test('match done finish action redirects to matches list', function () {
    $game = Game::factory()->create([
        'started_at' => now(),
        'best_of' => 1,
    ]);

    $component = Livewire::test('matches-score', ['gameId' => $game->id])
        ->call('selectServe', 'left');

    for ($i = 0; $i < 11; $i++) {
        $component->call('awardLeftPoint');
    }

    $component
        ->assertSet('showMatchDoneOverlay', true)
        ->call('finishMatch')
        ->assertRedirect(route('matches.index'));
});
