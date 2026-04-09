<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

test('users can register for events', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $user->events()->attach($event);

    expect($user->events)->toHaveCount(1);
    expect($event->users)->toHaveCount(1);
});

test('seeders create the event and users', function () {
    config()->set('app.seed_user_password', 'seed-user-password-123');

    $this->seed();

    $event = Event::first();
    $adminEmail = env('ADMIN_EMAIL');
    $vedran = User::query()->where('email', 'vedran.zbulj@gmail.com')->first();
    $nonAdminUser = User::query()->where('email', 'igor.levak@example.com')->first();

    expect(Event::count())->toBe(1);
    expect(User::count())->toBeGreaterThan(0);
    expect($event)->not()->toBeNull();
    expect($event?->users)->toHaveCount(User::count());
    expect($vedran)->not()->toBeNull();
    expect($vedran?->hasRole(RoleName::Admin->value))->toBeTrue();

    expect(Role::query()->pluck('name')->all())->toEqualCanonicalizing([
        RoleName::Player->value,
        RoleName::Admin->value,
    ]);
    expect(User::role(RoleName::Player->value)->count())->toBe(User::count());
    expect($nonAdminUser)->not()->toBeNull();
    expect(Hash::check('seed-user-password-123', (string) $nonAdminUser?->password))->toBeTrue();
    expect(Hash::check('seed-user-password-123', (string) $vedran?->password))->toBeFalse();

    if (filled($adminEmail)) {
        expect(User::where('email', $adminEmail)->first()?->hasRole(RoleName::Admin->value))->toBeTrue();
    }
});
