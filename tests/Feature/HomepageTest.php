<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('homepage loads', function () {
    $this->withoutVite();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Squash Marathon 24h');
    $response->assertSee('Poredak');
    $response->assertSee('Ekipa');
    $response->assertSee('images/arena.jpg', false);
});

test('header shows login action for guests', function () {
    $this->withoutVite();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Prijava');
    $response->assertDontSee('Odjava');
});

test('header shows logout action for authenticated users', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertSuccessful();
    $response->assertSee('Odjava');
    $response->assertDontSee('Prijava');
    $response->assertSee(route('profile'), false);
});

test('profile page redirects guests to login', function () {
    $this->withoutVite();

    $response = $this->get(route('profile'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can load profile page', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile'));

    $response->assertSuccessful();
});

test('homepage shows login success toast when status is flashed', function () {
    $this->withoutVite();

    $response = $this->withSession(['status' => 'Prijavljeni ste'])->get('/');

    $response->assertSuccessful();
    $response->assertSee('Prijavljeni ste');
    $response->assertSee('Zatvori obavijest');
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
        'best_of' => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    $response = $this->get('/');

    $response->assertSee($playerOne->full_name);
    $response->assertSee($playerTwo->full_name);
    $response->assertSee($playerOne->initials);
    $response->assertSee($playerTwo->initials);
    $response->assertSee(route('players.show', $playerOne), false);
    $response->assertSee(route('players.show', $playerTwo), false);
    $response->assertSee('11-6');
});

test('leaderboard livewire component shows players and points', function () {
    $event = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'best_of' => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
    ]);

    Livewire::test('leaderboard')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee($playerOne->short_name)
        ->assertSee($playerTwo->short_name)
        ->assertSee(route('players.show', $playerOne), false)
        ->assertSee(route('players.show', $playerTwo), false)
        ->assertSee('3')
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
        'best_of' => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'created_at' => $gameTime,
        'updated_at' => $gameTime,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
        'created_at' => $gameTime,
        'updated_at' => $gameTime,
    ]);

    GameSet::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
        'created_at' => $gameTime,
        'updated_at' => $gameTime,
    ]);

    Livewire::test('timeline')
        ->assertSee('Kronologija')
        ->assertSee('Najsvježije završene partije')
        ->assertSee('Zadnjih 24 završenih partija.')
        ->assertSee($playerOne->full_name)
        ->assertSee($playerTwo->full_name)
        ->assertSee('11-6')
        ->assertSee('Trajanje');
});

test('timeline livewire orders games by finished_at descending', function () {
    $event = Event::factory()->create();
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

    $olderPlayerOne = User::factory()->create();
    $olderPlayerTwo = User::factory()->create();
    $newerPlayerOne = User::factory()->create();
    $newerPlayerTwo = User::factory()->create();

    $event->users()->attach([
        $olderPlayerOne->id,
        $olderPlayerTwo->id,
        $newerPlayerOne->id,
        $newerPlayerTwo->id,
    ]);

    $olderGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'best_of' => 1,
        'player_one_id' => $olderPlayerOne->id,
        'player_two_id' => $olderPlayerTwo->id,
        'created_at' => now()->subMinutes(1),
        'updated_at' => now()->subMinutes(1),
        'started_at' => now()->subMinutes(15),
        'finished_at' => now()->subMinutes(10),
    ]);

    $newerGame = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'best_of' => 1,
        'player_one_id' => $newerPlayerOne->id,
        'player_two_id' => $newerPlayerTwo->id,
        'created_at' => now()->subMinutes(30),
        'updated_at' => now()->subMinutes(30),
        'started_at' => now()->subMinutes(8),
        'finished_at' => now()->subMinutes(3),
    ]);

    GameSet::factory()->create([
        'game_id' => $olderGame->id,
        'player_one_id' => $olderPlayerOne->id,
        'player_two_id' => $olderPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    GameSet::factory()->create([
        'game_id' => $newerGame->id,
        'player_one_id' => $newerPlayerOne->id,
        'player_two_id' => $newerPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
    ]);

    Livewire::test('timeline')
        ->assertSeeInOrder([
            $newerPlayerOne->full_name,
            $olderPlayerOne->full_name,
        ]);
});
