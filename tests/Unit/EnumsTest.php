<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Enums\RoleName;

// ---------------------------------------------------------------------------
// GameLogSide
// ---------------------------------------------------------------------------

test('GameLogSide values returns left and right', function () {
    expect(GameLogSide::values())->toBe(['left', 'right']);
});

// ---------------------------------------------------------------------------
// GameLogType
// ---------------------------------------------------------------------------

test('GameLogType values returns score let and stroke', function () {
    expect(GameLogType::values())->toBe(['score', 'let', 'stroke']);
});

// ---------------------------------------------------------------------------
// RoleName
// ---------------------------------------------------------------------------

test('RoleName values returns player and admin', function () {
    expect(RoleName::values())->toBe(['player', 'admin']);
});

test('RoleName labels returns expected map', function () {
    expect(RoleName::labels())->toBe([
        'player' => 'Player',
        'admin'  => 'Admin',
    ]);
});
