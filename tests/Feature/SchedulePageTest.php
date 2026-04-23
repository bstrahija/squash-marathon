<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\GameSchedule;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;

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
        'end_at' => now()->addHour(),
    ]);

    $activeRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 2,
        'name' => 'Runda 2',
        'is_active' => true,
    ]);

    $inactiveRound = Round::factory()->create([
        'event_id' => $event->id,
        'number' => 1,
        'name' => 'Runda 1',
        'is_active' => false,
    ]);

    $groupOne = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number' => 1,
        'name' => 'Grupa 1',
    ]);

    $groupTwo = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number' => 2,
        'name' => 'Grupa 2',
    ]);

    $groupThree = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'number' => 3,
        'name' => 'Grupa 3',
    ]);

    $inactiveGroup = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $inactiveRound->id,
        'number' => 1,
        'name' => 'Stara grupa',
    ]);

    $playerOne = User::factory()->create(['first_name' => 'Adam', 'last_name' => 'Alpha']);
    $playerTwo = User::factory()->create(['first_name' => 'Boris', 'last_name' => 'Beta']);
    $playerThree = User::factory()->create(['first_name' => 'Cedo', 'last_name' => 'Gamma']);
    $playerFour = User::factory()->create(['first_name' => 'Dario', 'last_name' => 'Delta']);
    $finishedOnlyPlayer = User::factory()->create(['first_name' => 'Zoran', 'last_name' => 'Finished']);

    $liveGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'group_id' => $groupOne->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'started_at' => now()->subMinutes(2),
        'finished_at' => null,
    ]);

    $finishedGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $activeRound->id,
        'group_id' => $groupOne->id,
        'player_one_id' => $finishedOnlyPlayer->id,
        'player_two_id' => $playerThree->id,
        'started_at' => now()->subMinutes(8),
        'finished_at' => now()->subMinutes(1),
    ]);

    GameSchedule::factory()->create([
        'round_id' => $activeRound->id,
        'group_id' => $groupOne->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'game_id' => $liveGame->id,
    ]);

    GameSchedule::factory()->create([
        'round_id' => $activeRound->id,
        'group_id' => $groupOne->id,
        'player_one_id' => $finishedOnlyPlayer->id,
        'player_two_id' => $playerThree->id,
        'game_id' => $finishedGame->id,
    ]);

    GameSchedule::factory()->create([
        'round_id' => $activeRound->id,
        'group_id' => $groupTwo->id,
        'player_one_id' => $playerThree->id,
        'player_two_id' => $playerFour->id,
        'game_id' => null,
    ]);

    GameSchedule::factory()->create([
        'round_id' => $activeRound->id,
        'group_id' => $groupThree->id,
        'player_one_id' => $playerFour->id,
        'player_two_id' => $playerOne->id,
        'game_id' => null,
    ]);

    GameSchedule::factory()->create([
        'round_id' => $inactiveRound->id,
        'group_id' => $inactiveGroup->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerThree->id,
        'game_id' => null,
    ]);

    $response = $this->get('/schedule');

    $response->assertSuccessful();
    $response->assertSee('Grupa 1');
    $response->assertSee('Grupa 2');
    $response->assertDontSee('Grupa 3');
    $response->assertDontSee('Stara grupa');
    $response->assertSee('LIVE');
    $response->assertDontSee($finishedOnlyPlayer->short_name);
});
