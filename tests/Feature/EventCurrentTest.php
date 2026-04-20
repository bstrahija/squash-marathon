<?php

use App\Models\Event;
use Illuminate\Support\Carbon;

test('event current returns null when there are no events', function () {
    expect(Event::current())->toBeNull();
});

test('event current returns the latest inserted event regardless of event dates', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 20, 12, 0, 0));

    $olderActiveEvent = Event::factory()->create([
        'name'     => 'Older Active Event',
        'start_at' => now()->subHour(),
        'end_at'   => now()->addHour(),
    ]);

    $newerFutureEvent = Event::factory()->create([
        'name'     => 'Newer Future Event',
        'start_at' => now()->addDays(2),
        'end_at'   => now()->addDays(3),
    ]);

    $current = Event::current();

    expect($current)->not->toBeNull();
    expect($current?->id)->toBe($newerFutureEvent->id);
    expect($current?->name)->toBe('Newer Future Event');
    expect($current?->id)->not->toBe($olderActiveEvent->id);

    Carbon::setTestNow();
});
