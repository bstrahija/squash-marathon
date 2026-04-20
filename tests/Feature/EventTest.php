<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('throws when end_at is the same as start_at', function () {
    $time = CarbonImmutable::now();

    expect(fn () => Event::factory()->create([
        'start_at' => $time,
        'end_at'   => $time,
    ]))->toThrow(InvalidArgumentException::class, 'Event end_at must be after start_at.');
});

test('throws when end_at is before start_at', function () {
    expect(fn () => Event::factory()->create([
        'start_at' => now()->addHour(),
        'end_at'   => now(),
    ]))->toThrow(InvalidArgumentException::class, 'Event end_at must be after start_at.');
});

test('accepts an event with valid start and end times', function () {
    $event = Event::factory()->create([
        'start_at' => now(),
        'end_at'   => now()->addDay(),
    ]);

    expect($event->exists)->toBeTrue();
});

test('resolveParticipants returns event users when they exist', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event   = Event::factory()->create();
    $players = User::factory()->count(3)->create();
    $players->each(fn (User $u) => $u->assignRole(RoleName::Player->value));
    $event->users()->sync($players->pluck('id'));

    $result = $event->resolveParticipants();

    expect($result->pluck('id')->sort()->values()->all())
        ->toBe($players->pluck('id')->sort()->values()->all());
});

test('resolveParticipants falls back to role players when event has no attached users', function () {
    Role::firstOrCreate(['name' => RoleName::Player->value]);

    $event = Event::factory()->create();
    User::factory()->count(2)->create()->each(fn (User $u) => $u->assignRole(RoleName::Player->value));

    $result = $event->resolveParticipants();

    expect($result)->not->toBeEmpty();
    $result->each(fn ($u) => expect($u->hasRole(RoleName::Player->value))->toBeTrue());
});

test('resolveParticipants falls back to all users when no role players exist', function () {
    // The create_admin_user migration seeds an admin user with the Player role.
    // Strip that role so we can test the third fallback (all users).
    User::role(RoleName::Player->value)->get()->each(fn (User $u) => $u->removeRole(RoleName::Player->value));
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $event = Event::factory()->create();
    User::factory()->count(2)->create();

    $result = $event->resolveParticipants();

    expect($result->count())->toBeGreaterThanOrEqual(2);
});

test('current returns null when no events exist', function () {
    expect(Event::current())->toBeNull();
});

test('current returns the latest event by start_at', function () {
    Event::factory()->create(['start_at' => now()->subDay(), 'end_at' => now()->addDay()]);
    $newest = Event::factory()->create(['start_at' => now(), 'end_at' => now()->addDays(2)]);

    expect(Event::current()?->id)->toBe($newest->id);
});

test('latestCompletedGames returns only complete games', function () {
    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    $group = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id]);

    $completeGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $completeGame->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    GameSet::factory()->create([
        'game_id'          => $completeGame->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 4,
    ]);

    // Incomplete game: best_of=3, only 1 set played
    $incompleteGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 3,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $incompleteGame->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    $result = $event->latestCompletedGames();

    expect($result->pluck('id')->all())->toBe([$completeGame->id]);
});

test('latestCompletedGames respects the limit parameter', function () {
    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    $group = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id]);

    for ($i = 0; $i < 5; $i++) {
        $game = Game::factory()->create([
            'event_id'      => $event->id,
            'round_id'      => $round->id,
            'group_id'      => $group->id,
            'best_of'       => 2,
            'player_one_id' => $playerOne->id,
            'player_two_id' => $playerTwo->id,
        ]);

        GameSet::factory()->create([
            'game_id'          => $game->id,
            'player_one_id'    => $playerOne->id,
            'player_two_id'    => $playerTwo->id,
            'player_one_score' => 11,
            'player_two_score' => 7,
        ]);

        GameSet::factory()->create([
            'game_id'          => $game->id,
            'player_one_id'    => $playerOne->id,
            'player_two_id'    => $playerTwo->id,
            'player_one_score' => 11,
            'player_two_score' => 4,
        ]);
    }

    expect($event->latestCompletedGames(3))->toHaveCount(3);
});

test('photoUrl returns the placeholder SVG when no media is attached', function () {
    $event = Event::factory()->create();

    expect($event->photoUrl())->toContain('placeholder-event.svg');
});

test('getFallbackMediaUrl returns empty string for non-photo collections', function () {
    $event = Event::factory()->create();

    expect($event->getFallbackMediaUrl('other'))->toBe('');
});

test('getFallbackMediaUrl returns placeholder for the photo collection', function () {
    $event = Event::factory()->create();

    expect($event->getFallbackMediaUrl('photo'))->toContain('placeholder-event.svg');
});
