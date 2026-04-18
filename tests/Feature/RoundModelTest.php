<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ---------------------------------------------------------------------------
// nextNumberForEvent
// ---------------------------------------------------------------------------

test('next number for event returns 1 when no rounds exist', function () {
    $event = Event::factory()->create();

    expect(Round::nextNumberForEvent($event->id))->toBe(1);
});

test('next number for event increments above the highest round number', function () {
    $event = Event::factory()->create();

    Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    Round::factory()->create(['event_id' => $event->id, 'number' => 2]);

    expect(Round::nextNumberForEvent($event->id))->toBe(3);
});

// ---------------------------------------------------------------------------
// previousForEvent
// ---------------------------------------------------------------------------

test('previous round for event returns null when before round number is less than 2', function () {
    $event = Event::factory()->create();

    expect(Round::previousForEvent($event->id, 1))->toBeNull();
    expect(Round::previousForEvent($event->id, 0))->toBeNull();
});

test('previous round for event returns the round with the highest number below the given number', function () {
    $event = Event::factory()->create();

    $round1 = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    Round::factory()->create(['event_id' => $event->id, 'number' => 2]);

    $previous = Round::previousForEvent($event->id, 2);

    expect($previous)->not->toBeNull();
    expect($previous->id)->toBe($round1->id);
});

// ---------------------------------------------------------------------------
// createForEventWithGroups
// ---------------------------------------------------------------------------

test('createForEventWithGroups creates round with two groups and assigns players', function () {
    $event = Event::factory()->create();

    $groupOnePlayers = User::factory()->count(2)->create();
    $groupTwoPlayers = User::factory()->count(2)->create();

    $round = Round::createForEventWithGroups(
        $event->id,
        $groupOnePlayers->pluck('id')->all(),
        $groupTwoPlayers->pluck('id')->all(),
    );

    expect($round->event_id)->toBe($event->id);
    expect($round->number)->toBe(1);
    expect($round->name)->toBe('Runda 1');
    expect($round->is_active)->toBeTrue();

    $groups = $round->groups()->with('users')->get();
    expect($groups)->toHaveCount(2);

    $group1 = $groups->firstWhere('number', 1);
    $group2 = $groups->firstWhere('number', 2);

    expect($group1->users->pluck('id')->sort()->values()->all())
        ->toBe($groupOnePlayers->pluck('id')->sort()->values()->all());

    expect($group2->users->pluck('id')->sort()->values()->all())
        ->toBe($groupTwoPlayers->pluck('id')->sort()->values()->all());
});

test('createForEventWithGroups deactivates previous rounds', function () {
    $event = Event::factory()->create();

    $existing = Round::factory()->create(['event_id' => $event->id, 'number' => 1, 'is_active' => true]);

    Round::createForEventWithGroups($event->id, [], []);

    expect($existing->fresh()->is_active)->toBeFalse();
});

// ---------------------------------------------------------------------------
// updateForEventWithGroups
// ---------------------------------------------------------------------------

test('updateForEventWithGroups updates the round name and syncs group players', function () {
    $event = Event::factory()->create();

    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1, 'name' => 'Old Name']);

    $players = User::factory()->count(4)->create();

    Round::updateForEventWithGroups(
        $round->id,
        $event->id,
        'New Name',
        [$players[0]->id, $players[1]->id],
        [$players[2]->id, $players[3]->id],
    );

    expect($round->fresh()->name)->toBe('New Name');

    $group1 = Group::where('round_id', $round->id)->where('number', 1)->with('users')->first();
    $group2 = Group::where('round_id', $round->id)->where('number', 2)->with('users')->first();

    expect($group1->users->pluck('id')->sort()->values()->all())
        ->toBe(collect([$players[0]->id, $players[1]->id])->sort()->values()->all());

    expect($group2->users->pluck('id')->sort()->values()->all())
        ->toBe(collect([$players[2]->id, $players[3]->id])->sort()->values()->all());
});

test('updateForEventWithGroups throws ModelNotFoundException for unknown round', function () {
    $event = Event::factory()->create();

    expect(fn () => Round::updateForEventWithGroups(99999, $event->id, 'Name', [], []))
        ->toThrow(ModelNotFoundException::class);
});

// ---------------------------------------------------------------------------
// splitPlayersFromPreviousRoundByPoints
// ---------------------------------------------------------------------------

test('splitPlayersFromPreviousRoundByPoints returns empty arrays when no groups exist', function () {
    $event = Event::factory()->create();

    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);

    $previousRound = Round::with(['groups.users'])->find($round->id);

    [$groupOne, $groupTwo] = Round::splitPlayersFromPreviousRoundByPoints(
        $previousRound,
        collect(),
    );

    expect($groupOne)->toBe([]);
    expect($groupTwo)->toBe([]);
});

test('splitPlayersFromPreviousRoundByPoints promotes winner of each group to group one', function () {
    $event = Event::factory()->create();

    [$p1, $p2, $p3, $p4] = User::factory()->count(4)->create()->all();

    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
    ]);
    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 2,
    ]);

    $groupOne->users()->sync([$p1->id, $p2->id]);
    $groupTwo->users()->sync([$p3->id, $p4->id]);

    // p1 wins in group 1 (2–0), best_of=2 means exactly 2 sets
    $game1 = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $groupOne->id,
        'player_one_id' => $p1->id,
        'player_two_id' => $p2->id,
        'best_of'       => 2,
    ]);
    GameSet::factory()->create(['game_id' => $game1->id, 'player_one_score' => 11, 'player_two_score' => 5, 'player_one_id' => $p1->id, 'player_two_id' => $p2->id]);
    GameSet::factory()->create(['game_id' => $game1->id, 'player_one_score' => 11, 'player_two_score' => 3, 'player_one_id' => $p1->id, 'player_two_id' => $p2->id]);

    // p3 wins in group 2 (2–0), best_of=2 means exactly 2 sets
    $game2 = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $groupTwo->id,
        'player_one_id' => $p3->id,
        'player_two_id' => $p4->id,
        'best_of'       => 2,
    ]);
    GameSet::factory()->create(['game_id' => $game2->id, 'player_one_score' => 11, 'player_two_score' => 4, 'player_one_id' => $p3->id, 'player_two_id' => $p4->id]);
    GameSet::factory()->create(['game_id' => $game2->id, 'player_one_score' => 11, 'player_two_score' => 6, 'player_one_id' => $p3->id, 'player_two_id' => $p4->id]);

    $previousRound    = Round::with(['groups.users'])->find($round->id);
    $eventPlayersById = collect([$p1->id => $p1, $p2->id => $p2, $p3->id => $p3, $p4->id => $p4]);

    [$nextGroupOne, $nextGroupTwo] = Round::splitPlayersFromPreviousRoundByPoints($previousRound, $eventPlayersById);

    // Winners (p1 from group1, p3 from group2) should be in next group one
    expect($nextGroupOne)->toContain($p1->id);
    expect($nextGroupOne)->toContain($p3->id);

    // Losers (p2, p4) should be in next group two
    expect($nextGroupTwo)->toContain($p2->id);
    expect($nextGroupTwo)->toContain($p4->id);
});

test('splitPlayersFromPreviousRoundByPoints breaks ties alphabetically', function () {
    $event = Event::factory()->create();

    // Create players with known names so alphabetical order is predictable
    $pAlpha = User::factory()->create(['first_name' => 'Alpha', 'last_name' => 'Player']);
    $pZeta  = User::factory()->create(['first_name' => 'Zeta', 'last_name' => 'Player']);

    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);

    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
    ]);

    $group->users()->sync([$pAlpha->id, $pZeta->id]);

    // No completed games — both players have 0 points, tie broken by name
    $previousRound    = Round::with(['groups.users'])->find($round->id);
    $eventPlayersById = collect([$pAlpha->id => $pAlpha, $pZeta->id => $pZeta]);

    [$nextGroupOne, $nextGroupTwo] = Round::splitPlayersFromPreviousRoundByPoints($previousRound, $eventPlayersById);

    // Alpha should rank first alphabetically and go to group one
    expect($nextGroupOne)->toContain($pAlpha->id);
    expect($nextGroupTwo)->toContain($pZeta->id);
});
