<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\Set;
use App\Models\User;

test('group connects events, users, games, and sets', function () {
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

    $group->users()->sync([$playerOne->id, $playerTwo->id]);

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
        'player_two_score' => 7,
    ]);

    expect($group->event->is($event))->toBeTrue();
    expect($group->round->is($round))->toBeTrue();
    expect($group->users)->toHaveCount(2);
    expect($group->games)->toHaveCount(1);
    expect($group->sets)->toHaveCount(1);
});
