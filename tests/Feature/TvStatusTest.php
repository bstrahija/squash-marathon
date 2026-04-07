<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\Set;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('tv status page loads', function () {
    $this->withoutVite();

    $response = $this->get('/tv');

    $response->assertSuccessful();
    $response->assertSee('tv-container');
    $response->assertSee('h-screen');
    $response->assertSee('grid-cols-3');
});

test('event countdown livewire component renders event details', function () {
    $now = Carbon::create(2026, 2, 27, 20, 0, 0);
    Carbon::setTestNow($now);

    Event::factory()->create([
        'name' => 'Maraton 2026',
        'start_at' => $now->copy()->subHours(2),
        'end_at' => $now->copy()->addMinutes(30),
    ]);

    Livewire::test('event-countdown')
        ->assertSee('Maraton 2026')
        ->assertSee('20:30');

    Carbon::setTestNow();
});

test('latest games livewire component shows recent games', function () {
    $event = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $gameTime = Carbon::create(2026, 2, 27, 19, 50, 0);

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'best_of' => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'created_at' => $gameTime,
        'updated_at' => $gameTime,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
        'created_at' => $gameTime,
        'updated_at' => $gameTime,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
        'created_at' => $gameTime,
        'updated_at' => $gameTime,
    ]);

    Livewire::test('latest-games')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee('11-6')
        ->assertSee('19:50');
});

test('tv leaderboard livewire component shows all event players', function () {
    $event = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $playerThree = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id, $playerThree->id]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'best_of' => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    Livewire::test('tv.leaderboard')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee($playerThree->full_name);
});

test('tv latest games component shows last 30 games with result and duration', function () {
    $event = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $baseTime = Carbon::create(2026, 2, 27, 18, 0, 0);
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

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    foreach (range(1, 31) as $index) {
        $game = Game::factory()->create([
            'event_id' => $event->id,
            'round_id' => $round->id,
            'group_id' => $group->id,
            'best_of' => 2,
            'court_number' => 1,
            'player_one_id' => $playerOne->id,
            'player_two_id' => $playerTwo->id,
            'duration_seconds' => 60 + $index,
            'created_at' => $baseTime->copy()->addMinutes($index),
            'updated_at' => $baseTime->copy()->addMinutes($index),
        ]);

        Set::factory()->create([
            'game_id' => $game->id,
            'player_one_id' => $playerOne->id,
            'player_two_id' => $playerTwo->id,
            'player_one_score' => 11,
            'player_two_score' => 5,
        ]);

        Set::factory()->create([
            'game_id' => $game->id,
            'player_one_id' => $playerOne->id,
            'player_two_id' => $playerTwo->id,
            'player_one_score' => 11,
            'player_two_score' => 7,
        ]);
    }

    Livewire::test('tv.latest-games')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee('Rezultat 11-5, 11-7')
        ->assertSee('Trajanje 1:31')
        ->assertDontSee('Trajanje 1:01');
});
