<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\Set;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('homepage loads', function () {
    $this->withoutVite();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Squash maraton - praćenje');
    $response->assertSee('Poredak');
    $response->assertSee('Ekipa');
});

test('homepage renders real data sections', function () {
    $this->withoutVite();

    Role::findOrCreate(RoleName::Player->value);

    $event = Event::factory()->create();
    $playerOne = User::factory()->create()->assignRole(RoleName::Player->value);
    $playerTwo = User::factory()->create()->assignRole(RoleName::Player->value);

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'best_of' => 1,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);

    $response = $this->get('/');

    $response->assertSee($playerOne->full_name);
    $response->assertSee($playerTwo->full_name);
    $response->assertSee('11-6');
});

test('leaderboard livewire component shows players and points', function () {
    $event = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'best_of' => 1,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);

    Livewire::test('leaderboard')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee('2')
        ->assertSee('1');
});

test('timeline livewire component shows recent games', function () {
    $event = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $gameTime = Carbon::create(2026, 2, 1, 19, 45, 0);

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'best_of' => 1,
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

    Livewire::test('timeline')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee('11-6')
        ->assertSee('19:45');
});
