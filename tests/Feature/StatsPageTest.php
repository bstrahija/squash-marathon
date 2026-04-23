<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use Livewire\Livewire;

test('stats page loads', function () {
    $this->withoutVite();

    $response = $this->get('/stats');

    $response->assertSuccessful();
    $response->assertSee('Statistika');
    $response->assertSee('Igrač');
    $response->assertSee('Poredak');
    $response->assertDontSee('Bodovi nakon svakog meča');
    $response->assertDontSee('Pobjeda = 3 boda, remi = 2 boda, poraz = 1 bod');
});

test('main stats leaderboard can be sorted from header clicks', function () {
    $event = Event::factory()->create();

    $zulu = User::factory()->create([
        'first_name' => 'Zulu',
        'last_name'  => 'Player',
    ]);
    $ana = User::factory()->create([
        'first_name' => 'Ana',
        'last_name'  => 'Player',
    ]);
    $mark = User::factory()->create([
        'first_name' => 'Mark',
        'last_name'  => 'Player',
    ]);

    $event->users()->attach([$zulu->id, $ana->id, $mark->id]);

    $gameOne = Game::factory()->create([
        'event_id'      => $event->id,
        'best_of'       => 1,
        'player_one_id' => $zulu->id,
        'player_two_id' => $ana->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $gameOne->id,
        'round_id'         => $gameOne->round_id,
        'group_id'         => $gameOne->group_id,
        'player_one_id'    => $zulu->id,
        'player_two_id'    => $ana->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    $gameTwo = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $gameOne->round_id,
        'group_id'      => $gameOne->group_id,
        'best_of'       => 1,
        'player_one_id' => $ana->id,
        'player_two_id' => $mark->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $gameTwo->id,
        'round_id'         => $gameTwo->round_id,
        'group_id'         => $gameTwo->group_id,
        'player_one_id'    => $ana->id,
        'player_two_id'    => $mark->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    Livewire::test('stats.leaderboard')
        ->assertSet('sortBy', 'points')
        ->assertSet('sortDirection', 'desc')
        ->call('sortByColumn', 'name')
        ->assertSet('sortBy', 'points')
        ->assertSet('sortDirection', 'desc')
        ->call('sortByColumn', 'wins')
        ->assertSet('sortBy', 'wins')
        ->assertSet('sortDirection', 'desc')
        ->call('sortByColumn', 'name')
        ->assertSet('sortBy', 'wins')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder([$ana->full_name, $zulu->full_name, $mark->full_name])
        ->call('sortByColumn', 'wins')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder([$mark->full_name, $ana->full_name, $zulu->full_name])
        ->call('sortByColumn', 'sets_difference')
        ->assertSet('sortBy', 'sets_difference')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder([$zulu->full_name, $ana->full_name, $mark->full_name])
        ->call('sortByColumn', 'sets_difference')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder([$mark->full_name, $ana->full_name, $zulu->full_name])
        ->call('sortByColumn', 'points_difference')
        ->assertSet('sortBy', 'points_difference')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder([$zulu->full_name, $ana->full_name, $mark->full_name])
        ->call('sortByColumn', 'points_difference')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder([$mark->full_name, $ana->full_name, $zulu->full_name])
        ->call('sortByColumn', 'efficiency')
        ->assertSet('sortBy', 'efficiency')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder([$zulu->full_name, $ana->full_name, $mark->full_name])
        ->call('sortByColumn', 'efficiency')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder([$mark->full_name, $ana->full_name, $zulu->full_name]);
});
