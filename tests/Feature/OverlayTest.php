<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameLog;
use App\Models\GameSet;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Livewire\Livewire;

test('overlay group page loads', function () {
    $this->withoutVite();

    $response = $this->get('/overlay/1');

    $response->assertSuccessful();
});

test('overlay group page has transparent background', function () {
    $this->withoutVite();

    $response = $this->get('/overlay/1');

    $response->assertSuccessful();
    $response->assertSee('background: transparent', false);
});

test('overlay group page uses named route', function () {
    expect(route('overlay.group', ['group' => 1]))->toContain('/overlay/1');
    expect(route('overlay.group', ['group' => 2]))->toContain('/overlay/2');
});

test('overlay group component renders nothing when no event exists', function () {
    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertDontSee('UŽIVO')
        ->assertDontSee('ZAVRŠENO')
        ->assertDontSee('NA ČEKANJU');
});

test('overlay group component shows player names and score for live game', function () {
    $event    = Event::factory()->create();
    $round    = Round::factory()->create(['event_id' => $event->id, 'number' => 1, 'name' => 'Round 1']);
    $group    = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1, 'name' => 'Teren 1']);
    $player   = User::factory()->create(['first_name' => 'Ana', 'last_name' => 'Kovač']);
    $opponent = User::factory()->create(['first_name' => 'Ivan', 'last_name' => 'Horvat']);

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'group_id'      => $group->id,
        'round_id'      => $round->id,
        'best_of'       => 2,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'started_at'    => now()->subMinutes(10),
        'finished_at'   => null,
    ]);

    GameLog::factory()->create([
        'game_id'           => $game->id,
        'player_one_id'     => $player->id,
        'player_two_id'     => $opponent->id,
        'sequence'          => 1,
        'type'              => GameLogType::Score,
        'side'              => GameLogSide::Left,
        'serving_player_id' => $player->id,
        'player_one_score'  => 7,
        'player_two_score'  => 4,
        'player_one_sets'   => 0,
        'player_two_sets'   => 0,
    ]);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertSee($player->short_name)
        ->assertSee($opponent->short_name)
        ->assertSee('7')
        ->assertSee('4')
        ->assertSee('UŽIVO')
        ->assertSee('Teren 1');
});

test('overlay group component shows ZAVRŠENO status for finished game', function () {
    $event    = Event::factory()->create();
    $round    = Round::factory()->create(['event_id' => $event->id, 'number' => 1, 'name' => 'Round 1']);
    $group    = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1, 'name' => 'Teren 1']);
    $player   = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'group_id'      => $group->id,
        'round_id'      => $round->id,
        'best_of'       => 2,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'started_at'    => now()->subHour(),
        'finished_at'   => now()->subMinutes(10),
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $player->id,
        'player_two_id'    => $opponent->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $player->id,
        'player_two_id'    => $opponent->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertSee('ZAVRŠENO')
        ->assertSee('11:6')
        ->assertSee('11:8');
});

test('overlay group component shows set history chips for completed sets', function () {
    $event    = Event::factory()->create();
    $round    = Round::factory()->create(['event_id' => $event->id, 'number' => 1, 'name' => 'Round 1']);
    $group    = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1, 'name' => 'Teren 1']);
    $player   = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'group_id'      => $group->id,
        'round_id'      => $round->id,
        'best_of'       => 2,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'started_at'    => now()->subMinutes(15),
        'finished_at'   => null,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $player->id,
        'player_two_id'    => $opponent->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
    ]);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertSee('11:9');
});

test('overlay group component shows 0 for player with no sets won', function () {
    $event    = Event::factory()->create();
    $round    = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    $group    = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1]);
    $player   = User::factory()->create();
    $opponent = User::factory()->create();

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'group_id'      => $group->id,
        'round_id'      => $round->id,
        'best_of'       => 3,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'started_at'    => now()->subMinutes(30),
        'finished_at'   => null,
    ]);

    // Player one has won one set, player two has won none
    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $player->id,
        'player_two_id'    => $opponent->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertSeeHtml('>1<')   // player one has 1 set won
        ->assertSeeHtml('>0<');  // player two shows 0
});

test('overlay group component shows duration with clock icon for live game', function () {
    $event    = Event::factory()->create();
    $round    = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    $group    = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1]);
    $player   = User::factory()->create();
    $opponent = User::factory()->create();

    Game::factory()->create([
        'event_id'      => $event->id,
        'group_id'      => $group->id,
        'round_id'      => $round->id,
        'best_of'       => 3,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'started_at'    => now()->subMinutes(15),
        'finished_at'   => null,
    ]);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertSee('15:')                       // duration label rendered
        ->assertSeeHtml('<svg', false);           // clock icon (blade icon renders as svg)
});

test('overlay group component shows duration for finished game', function () {
    $event    = Event::factory()->create();
    $round    = Round::factory()->create(['event_id' => $event->id, 'number' => 1]);
    $group    = Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 1]);
    $player   = User::factory()->create();
    $opponent = User::factory()->create();

    Game::factory()->create([
        'event_id'         => $event->id,
        'group_id'         => $group->id,
        'round_id'         => $round->id,
        'best_of'          => 3,
        'player_one_id'    => $player->id,
        'player_two_id'    => $opponent->id,
        'started_at'       => now()->subMinutes(32),
        'finished_at'      => now()->subMinutes(2),
        'duration_seconds' => 1800,              // 30:00
    ]);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertSee('30:00');
});

test('overlay group component renders nothing visible when no games exist for group', function () {
    $event = Event::factory()->create();
    $round = Round::factory()->create(['event_id' => $event->id, 'number' => 1, 'name' => 'Round 1']);
    Group::factory()->create(['event_id' => $event->id, 'round_id' => $round->id, 'number' => 2, 'name' => 'Teren 2']);

    Livewire::test('overlay.group-match', ['groupNumber' => 1])
        ->assertDontSee('UŽIVO')
        ->assertDontSee('ZAVRŠENO');
});
