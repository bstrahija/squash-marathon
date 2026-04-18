<?php

use App\Livewire\Concerns\HasGameDisplayHelpers;
use App\Models\Game;

// ---------------------------------------------------------------------------
// Test double — wraps the protected trait methods in public proxies
// ---------------------------------------------------------------------------

class GameDisplayTestDouble
{
    use HasGameDisplayHelpers;

    public function testFormatDuration(?int $seconds): string
    {
        return $this->formatDuration($seconds);
    }

    public function testPlayerClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        return $this->playerClass($playerId, $winnerId, $isDraw);
    }

    public function testSetScoreClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        return $this->setScoreClass($playerId, $winnerId, $isDraw);
    }

    public function testMatchDurationLabel(Game $game, bool $isLive): string
    {
        return $this->matchDurationLabel($game, $isLive);
    }
}

// ---------------------------------------------------------------------------
// formatDuration
// ---------------------------------------------------------------------------

test('formatDuration returns em dash for null', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testFormatDuration(null))->toBe('—');
});

test('formatDuration returns em dash for zero', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testFormatDuration(0))->toBe('—');
});

test('formatDuration formats seconds under one hour as MM:SS', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testFormatDuration(90))->toBe('1:30');
});

test('formatDuration formats exactly one hour as H:MM:SS', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testFormatDuration(3600))->toBe('1:00:00');
});

test('formatDuration formats mixed hours minutes and seconds', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testFormatDuration(3661))->toBe('1:01:01');
});

// ---------------------------------------------------------------------------
// playerClass
// ---------------------------------------------------------------------------

test('playerClass returns text-foreground when player id is null', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testPlayerClass(null, 1, false))->toBe('text-foreground');
});

test('playerClass returns amber class when draw', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testPlayerClass(1, null, true))->toBe('text-amber-600/90 dark:text-amber-400/90');
});

test('playerClass returns emerald class when player is the winner', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testPlayerClass(1, 1, false))->toBe('text-emerald-600 dark:text-emerald-400');
});

test('playerClass returns muted class when player is the loser', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testPlayerClass(2, 1, false))->toBe('text-foreground/70');
});

// ---------------------------------------------------------------------------
// setScoreClass
// ---------------------------------------------------------------------------

test('setScoreClass returns text-foreground when player id is null', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testSetScoreClass(null, 1, false))->toBe('text-foreground');
});

test('setScoreClass returns text-foreground when is draw', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testSetScoreClass(1, 1, true))->toBe('text-foreground');
});

test('setScoreClass returns text-foreground when winner id is null', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testSetScoreClass(1, null, false))->toBe('text-foreground');
});

test('setScoreClass returns text-foreground when player is the winner', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testSetScoreClass(1, 1, false))->toBe('text-foreground');
});

test('setScoreClass returns muted class when player is the loser', function () {
    $double = new GameDisplayTestDouble;

    expect($double->testSetScoreClass(2, 1, false))->toBe('text-foreground/60');
});

// ---------------------------------------------------------------------------
// matchDurationLabel
// ---------------------------------------------------------------------------

test('matchDurationLabel returns live elapsed time when game is live and has started_at', function () {
    $this->freezeTime();

    $double = new GameDisplayTestDouble;

    $game = Game::factory()->make([
        'started_at'       => now()->subSeconds(90),
        'finished_at'      => null,
        'duration_seconds' => null,
    ]);

    $label = $double->testMatchDurationLabel($game, true);

    expect($label)->toBe('1:30');
});

test('matchDurationLabel uses duration_seconds when not live', function () {
    $double = new GameDisplayTestDouble;

    $game = Game::factory()->make([
        'started_at'       => null,
        'finished_at'      => null,
        'duration_seconds' => 3661,
    ]);

    expect($double->testMatchDurationLabel($game, false))->toBe('1:01:01');
});

test('matchDurationLabel derives duration from timestamps when duration_seconds is absent', function () {
    $double = new GameDisplayTestDouble;

    $game = Game::factory()->make([
        'started_at'       => now()->subSeconds(120),
        'finished_at'      => now(),
        'duration_seconds' => null,
    ]);

    expect($double->testMatchDurationLabel($game, false))->toBe('2:00');
});

test('matchDurationLabel returns live elapsed from started_at when no finished_at and not live', function () {
    $this->freezeTime();

    $double = new GameDisplayTestDouble;

    $game = Game::factory()->make([
        'started_at'       => now()->subSeconds(65),
        'finished_at'      => null,
        'duration_seconds' => null,
    ]);

    expect($double->testMatchDurationLabel($game, false))->toBe('1:05');
});

test('matchDurationLabel returns em dash when no timestamps and not live', function () {
    $double = new GameDisplayTestDouble;

    $game = Game::factory()->make([
        'started_at'       => null,
        'finished_at'      => null,
        'duration_seconds' => null,
    ]);

    expect($double->testMatchDurationLabel($game, false))->toBe('—');
});
