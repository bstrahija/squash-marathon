<?php

use App\Models\Event;
use App\Models\User;

test('users can register for events', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $user->events()->attach($event);

    expect($user->events)->toHaveCount(1);
    expect($event->users)->toHaveCount(1);
});

test('seeders create the event and users', function () {
    $this->seed();

    $event = Event::first();

    expect(Event::count())->toBe(1);
    expect(User::count())->toBe(15);
    expect($event)->not()->toBeNull();
    expect($event?->users)->toHaveCount(15);
});
