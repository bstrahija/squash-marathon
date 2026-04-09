<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameLog;
use App\Models\GameSet;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('app reset truncates data and seeds only users and events', function () {
    $this->seed();

    expect(Round::count())->toBeGreaterThan(0);
    expect(Group::count())->toBeGreaterThan(0);
    expect(Game::count())->toBeGreaterThan(0);
    expect(GameSet::count())->toBeGreaterThan(0);
    expect(GameLog::count())->toBeGreaterThan(0);

    $this->artisan('app:reset')
        ->assertSuccessful();

    expect(User::count())->toBeGreaterThan(0);
    expect(Event::count())->toBe(1);
    expect(Round::count())->toBe(0);
    expect(Group::count())->toBe(0);
    expect(Game::count())->toBe(0);
    expect(GameSet::count())->toBe(0);
    expect(GameLog::count())->toBe(0);
    expect(DB::table('event_user')->count())->toBe(User::count());

    if (filled(env('ADMIN_EMAIL'))) {
        expect(User::query()->where('email', env('ADMIN_EMAIL'))->first()?->hasRole(RoleName::Admin->value))->toBeTrue();
    }
});
