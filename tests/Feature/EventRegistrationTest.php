<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\User;
use Spatie\Permission\Models\Role;

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
    $adminEmail = env('ADMIN_EMAIL');

    expect(Event::count())->toBe(1);
    expect(User::count())->toBeGreaterThan(0);
    expect($event)->not()->toBeNull();
    expect($event?->users)->toHaveCount(User::count());

    expect(Role::query()->pluck('name')->all())->toEqualCanonicalizing([
        RoleName::Player->value,
        RoleName::Admin->value,
    ]);
    expect(User::role(RoleName::Player->value)->count())->toBe(User::count());

    if (filled($adminEmail)) {
        expect(User::where('email', $adminEmail)->first()?->hasRole(RoleName::Admin->value))->toBeTrue();
    }
});
