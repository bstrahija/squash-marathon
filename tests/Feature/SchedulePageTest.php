<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSchedule;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('schedule page is publicly accessible', function () {
    $this->withoutVite();

    $response = $this->get('/schedule');

    $response->assertSuccessful();
    $response->assertSee('Raspored');
});

test('schedule page shows only active round groups one and two and hides finished linked games', function () {
    $this->withoutVite();

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at'   => now()->addHour(),
    ]);

    $activeRound = Round::factory()->create([
        'event_id'  => $event->id,
        'number'    => 2,
        'name'      => 'Runda 2',
        'is_active' => true,
    ]);

    $inactiveRound = Round::factory()->create([
        'event_id'  => $event->id,
        'number'    => 1,
        'name'      => 'Runda 1',
        'is_active' => false,
    ]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number'   => 1,
        'name'     => 'Grupa 1',
    ]);

    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number'   => 2,
        'name'     => 'Grupa 2',
    ]);

    $groupThree = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number'   => 3,
        'name'     => 'Grupa 3',
    ]);

    $inactiveGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $inactiveRound->id,
        'number'   => 1,
        'name'     => 'Stara grupa',
    ]);

    $playerOne          = User::factory()->create(['first_name' => 'Adam', 'last_name' => 'Alpha']);
    $playerTwo          = User::factory()->create(['first_name' => 'Boris', 'last_name' => 'Beta']);
    $playerThree        = User::factory()->create(['first_name' => 'Cedo', 'last_name' => 'Gamma']);
    $playerFour         = User::factory()->create(['first_name' => 'Dario', 'last_name' => 'Delta']);
    $finishedOnlyPlayer = User::factory()->create(['first_name' => 'Zoran', 'last_name' => 'Finished']);

    $liveGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $activeRound->id,
        'group_id'      => $groupOne->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'started_at'    => now()->subMinutes(2),
        'finished_at'   => null,
    ]);

    $finishedGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $activeRound->id,
        'group_id'      => $groupOne->id,
        'player_one_id' => $finishedOnlyPlayer->id,
        'player_two_id' => $playerThree->id,
        'started_at'    => now()->subMinutes(8),
        'finished_at'   => now()->subMinutes(1),
    ]);

    GameSchedule::factory()->create([
        'round_id'      => $activeRound->id,
        'group_id'      => $groupOne->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'game_id'       => $liveGame->id,
    ]);

    GameSchedule::factory()->create([
        'round_id'      => $activeRound->id,
        'group_id'      => $groupOne->id,
        'player_one_id' => $finishedOnlyPlayer->id,
        'player_two_id' => $playerThree->id,
        'game_id'       => $finishedGame->id,
    ]);

    GameSchedule::factory()->create([
        'round_id'      => $activeRound->id,
        'group_id'      => $groupTwo->id,
        'player_one_id' => $playerThree->id,
        'player_two_id' => $playerFour->id,
        'game_id'       => null,
    ]);

    GameSchedule::factory()->create([
        'round_id'      => $activeRound->id,
        'group_id'      => $groupThree->id,
        'player_one_id' => $playerFour->id,
        'player_two_id' => $playerOne->id,
        'game_id'       => null,
    ]);

    GameSchedule::factory()->create([
        'round_id'      => $inactiveRound->id,
        'group_id'      => $inactiveGroup->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerThree->id,
        'game_id'       => null,
    ]);

    $response = $this->get('/schedule');

    $response->assertSuccessful();
    $response->assertSee('Grupa 1');
    $response->assertSee('Grupa 2');
    $response->assertDontSee('Grupa 3');
    $response->assertDontSee('Stara grupa');
    $response->assertDontSee('LIVE');
    $response->assertSee('0:0');
    $response->assertDontSee($finishedOnlyPlayer->short_name);
});

test('schedule rows are clickable only for logged in users', function () {
    $this->withoutVite();

    $event = Event::factory()->create([
        'start_at' => now()->subHour(),
        'end_at'   => now()->addHour(),
    ]);

    $round = Round::factory()->create([
        'event_id'  => $event->id,
        'is_active' => true,
    ]);

    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
    ]);

    $schedule = GameSchedule::factory()->create([
        'round_id' => $round->id,
        'group_id' => $group->id,
    ]);

    $guestResponse = $this->get('/schedule');
    $guestResponse->assertSuccessful();
    $guestResponse->assertDontSee(route('schedule.play', ['gameSchedule' => $schedule->id]), false);

    Role::findOrCreate(RoleName::Player->value);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $authResponse = $this->actingAs($player)->get('/schedule');
    $authResponse->assertSuccessful();
    $authResponse->assertSee(route('schedule.play', ['gameSchedule' => $schedule->id]), false);
});

test('schedule play route creates game when missing and redirects to score page', function () {
    Role::findOrCreate(RoleName::Player->value);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $event = Event::factory()->create();
    $round = Round::factory()->create([
        'event_id' => $event->id,
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
    ]);

    $schedule = GameSchedule::factory()->create([
        'round_id' => $round->id,
        'group_id' => $group->id,
        'game_id'  => null,
    ]);

    $response = $this->actingAs($player)->get(route('schedule.play', ['gameSchedule' => $schedule->id]));

    $schedule->refresh();
    $createdGame = Game::query()->find($schedule->game_id);

    expect($createdGame)->not->toBeNull();
    expect($createdGame?->event_id)->toBe($event->id);
    expect($createdGame?->round_id)->toBe($round->id);
    expect($createdGame?->group_id)->toBe($group->id);
    expect($createdGame?->best_of)->toBe(2);
    expect($createdGame?->player_one_id)->toBe($schedule->player_one_id);
    expect($createdGame?->player_two_id)->toBe($schedule->player_two_id);

    $response->assertRedirect(route('matches.score', ['game' => $createdGame?->id]));
});

test('schedule play route reuses existing game and redirects to score page', function () {
    Role::findOrCreate(RoleName::Player->value);

    $player = User::factory()->create();
    $player->assignRole(RoleName::Player->value);

    $event = Event::factory()->create();
    $round = Round::factory()->create([
        'event_id' => $event->id,
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
    ]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
    ]);

    $schedule = GameSchedule::factory()->create([
        'round_id' => $round->id,
        'group_id' => $group->id,
        'game_id'  => $game->id,
    ]);

    $response = $this->actingAs($player)->get(route('schedule.play', ['gameSchedule' => $schedule->id]));

    expect(Game::query()->count())->toBe(1);

    $response->assertRedirect(route('matches.score', ['game' => $game->id]));
});
