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
    $extraAdminUser = filled($adminEmail) && $adminEmail !== 'test@example.com';

    expect(Event::count())->toBe(1);
    expect(User::count())->toBe($extraAdminUser ? 16 : 15);
    expect($event)->not()->toBeNull();
    expect($event?->users)->toHaveCount(User::count());

    expect(Role::query()->pluck('name')->all())->toEqualCanonicalizing([
        RoleName::Player->value,
        RoleName::Admin->value,
    ]);
    expect(User::where('email', 'test@example.com')->first()?->hasRole(RoleName::Player->value))->toBeTrue();

    if (filled($adminEmail)) {
        expect(User::where('email', $adminEmail)->first()?->hasRole(RoleName::Admin->value))->toBeTrue();
    }
});
