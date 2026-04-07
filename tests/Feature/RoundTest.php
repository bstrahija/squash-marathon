<?php

use App\Models\Event;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;

test('round connects event, users, and groups', function () {
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

    $user = User::factory()->create();
    $round->users()->sync([$user->id]);

    expect($round->event->is($event))->toBeTrue();
    expect($round->groups)->toHaveCount(1);
    expect($round->users)->toHaveCount(1);
    expect($group->round->is($round))->toBeTrue();
});
