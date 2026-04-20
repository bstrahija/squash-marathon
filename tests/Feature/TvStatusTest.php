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
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function tvShortName(User $user): string
{
    return $user->short_name;
}

test('tv status page loads', function () {
    $this->withoutVite();

    $response = $this->get('/tv');

    $response->assertSuccessful();
    $response->assertSee('tv-container');
    $response->assertSee('tv-grid');
    $response->assertSee('grid-cols-[2fr_1fr_1fr]');
    $response->assertSee('Recent Matches');
    $response->assertSee('Event End Countdown');
});

test('tv group page shows selected group match from url parameter', function () {
    $this->withoutVite();

    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 2,
        'name'     => 'Group 2',
    ]);

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'started_at'    => Carbon::create(2026, 2, 27, 20, 0, 0),
        'finished_at'   => null,
    ]);

    $response = $this->get('/tv/2');

    $response->assertSuccessful();
    $response->assertSee('tv-group-container');
    $response->assertSee('Group 2');
    $response->assertSee(tvShortName($playerOne));
    $response->assertSee(tvShortName($playerTwo));
    $response->assertSee(route('matches.score', $game), false);
});

test('tv event end countdown component shows remaining time and end time', function () {
    $now = Carbon::create(2026, 2, 27, 20, 0, 0);
    Carbon::setTestNow($now);

    Event::factory()->create([
        'name'     => 'Maraton 2026',
        'start_at' => $now->copy()->subHours(3),
        'end_at'   => $now->copy()->addHour(),
    ]);

    Livewire::test('tv.event-end-countdown')
        ->assertSee('Event End Countdown')
        ->assertSee('Maraton 2026')
        ->assertSee('01:00:00')
        ->assertSee('21:00');

    Carbon::setTestNow();
});

test('tv event end countdown component shows duration and start time when event has not started', function () {
    $now = Carbon::create(2026, 2, 27, 8, 0, 0);
    Carbon::setTestNow($now);

    Event::factory()->create([
        'name'     => 'Maraton 2026',
        'start_at' => $now->copy()->addHours(2),
        'end_at'   => $now->copy()->addHours(26),
    ]);

    Livewire::test('tv.event-end-countdown')
        ->assertSee('Countdown do kraja')
        ->assertSeeHtml('Maraton 2026')
        ->assertSee('24:00:00')
        ->assertSee('Počinje')
        ->assertSee('10:00');

    Carbon::setTestNow();
});

test('tv event end countdown shows Croatian day and month when event starts on a future date', function () {
    // now = Friday 2026-04-17, event starts next Sunday 2026-04-19 at 09:00
    $now = Carbon::create(2026, 4, 17, 12, 0, 0);
    Carbon::setTestNow($now);

    Event::factory()->create([
        'name'     => 'Maraton 2026',
        'start_at' => Carbon::create(2026, 4, 19, 9, 0, 0),
        'end_at'   => Carbon::create(2026, 4, 20, 9, 0, 0),
    ]);

    Livewire::test('tv.event-end-countdown')
        ->assertSee('Countdown do kraja')
        ->assertSee('24:00:00')
        ->assertSee('Počinje')
        ->assertSee('nedjelja')
        ->assertSee('travnja')
        ->assertSee('09:00');

    Carbon::setTestNow();
});

test('event countdown livewire component renders event details', function () {
    $now = Carbon::create(2026, 2, 27, 20, 0, 0);
    Carbon::setTestNow($now);

    Event::factory()->create([
        'name'     => 'Maraton 2026',
        'start_at' => $now->copy()->subHours(2),
        'end_at'   => $now->copy()->addMinutes(30),
    ]);

    Livewire::test('event-countdown')
        ->assertSee('Maraton 2026')
        ->assertSee('20:30');

    Carbon::setTestNow();
});

test('latest games livewire component shows recent games', function () {
    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $gameTime  = Carbon::create(2026, 2, 27, 19, 50, 0);

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'created_at'    => $gameTime,
        'updated_at'    => $gameTime,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
        'created_at'       => $gameTime,
        'updated_at'       => $gameTime,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
        'created_at'       => $gameTime,
        'updated_at'       => $gameTime,
    ]);

    Livewire::test('latest-games')
        ->assertSee($playerOne->short_name)
        ->assertSee($playerTwo->short_name)
        ->assertSee('11-6')
        ->assertSee('19:50');
});

test('tv leaderboard livewire component shows all event players', function () {
    $event       = Event::factory()->create();
    $playerOne   = User::factory()->create();
    $playerTwo   = User::factory()->create();
    $playerThree = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id, $playerThree->id]);

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
    ]);

    GameSet::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    Livewire::test('tv.leaderboard')
        ->assertSee(tvShortName($playerOne))
        ->assertSee(tvShortName($playerTwo))
        ->assertSee(tvShortName($playerThree))
        ->assertSee(route('players.show', $playerOne), false)
        ->assertSee(route('players.show', $playerTwo), false)
        ->assertSee(route('players.show', $playerThree), false);
});

test('tv latest games component shows last 30 games with result and duration', function () {
    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();
    $baseTime  = Carbon::create(2026, 2, 27, 18, 0, 0);
    $round     = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
        'name'     => 'Group 1',
    ]);

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    foreach (range(1, 31) as $index) {
        $game = Game::factory()->create([
            'event_id'         => $event->id,
            'round_id'         => $round->id,
            'group_id'         => $group->id,
            'best_of'          => 2,
            'player_one_id'    => $playerOne->id,
            'player_two_id'    => $playerTwo->id,
            'duration_seconds' => 60 + $index,
            'created_at'       => $baseTime->copy()->addMinutes($index),
            'updated_at'       => $baseTime->copy()->addMinutes($index),
        ]);

        GameSet::factory()->create([
            'game_id'          => $game->id,
            'player_one_id'    => $playerOne->id,
            'player_two_id'    => $playerTwo->id,
            'player_one_score' => 11,
            'player_two_score' => 5,
        ]);

        GameSet::factory()->create([
            'game_id'          => $game->id,
            'player_one_id'    => $playerOne->id,
            'player_two_id'    => $playerTwo->id,
            'player_one_score' => 11,
            'player_two_score' => 7,
        ]);
    }

    Livewire::test('tv.latest-games')
        ->assertSee($playerOne->short_name)
        ->assertSee($playerTwo->short_name)
        ->assertSee('Rezultat 11-5, 11-7')
        ->assertSee('1:31')
        ->assertDontSee('Trajanje 1:31')
        ->assertDontSee('Trajanje 1:01');
});

test('tv group match component prefers live game over finished game in same group', function () {
    $event             = Event::factory()->create();
    $livePlayerOne     = User::factory()->create();
    $livePlayerTwo     = User::factory()->create();
    $finishedPlayerOne = User::factory()->create();
    $finishedPlayerTwo = User::factory()->create();

    $event->users()->attach([
        $livePlayerOne->id,
        $livePlayerTwo->id,
        $finishedPlayerOne->id,
        $finishedPlayerTwo->id,
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
        'name'     => 'Group 1',
    ]);

    $finishedGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $finishedPlayerOne->id,
        'player_two_id' => $finishedPlayerTwo->id,
        'created_at'    => Carbon::create(2026, 2, 27, 19, 40, 0),
        'started_at'    => Carbon::create(2026, 2, 27, 19, 40, 0),
        'finished_at'   => Carbon::create(2026, 2, 27, 19, 55, 0),
    ]);

    GameSet::factory()->create([
        'game_id'          => $finishedGame->id,
        'player_one_id'    => $finishedPlayerOne->id,
        'player_two_id'    => $finishedPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);
    GameSet::factory()->create([
        'game_id'          => $finishedGame->id,
        'player_one_id'    => $finishedPlayerOne->id,
        'player_two_id'    => $finishedPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    $liveGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $livePlayerOne->id,
        'player_two_id' => $livePlayerTwo->id,
        'created_at'    => Carbon::create(2026, 2, 27, 19, 58, 0),
        'started_at'    => Carbon::create(2026, 2, 27, 19, 58, 0),
        'finished_at'   => null,
    ]);

    GameSet::factory()->create([
        'game_id'          => $liveGame->id,
        'player_one_id'    => $livePlayerOne->id,
        'player_two_id'    => $livePlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 9,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 2, 27, 20, 3, 0));

    Livewire::test('tv.group-match', ['groupNumber' => 1])
        ->assertSee(tvShortName($livePlayerOne))
        ->assertSee(tvShortName($livePlayerTwo))
        ->assertSee('UŽIVO')
        ->assertSee('5:00')
        ->assertSee(route('matches.score', $liveGame), false)
        ->assertDontSee(tvShortName($finishedPlayerOne))
        ->assertDontSee(tvShortName($finishedPlayerTwo));

    Carbon::setTestNow();
});

test('tv group match component falls back to latest finished game when no live game exists', function () {
    $event           = Event::factory()->create();
    $olderPlayerOne  = User::factory()->create();
    $olderPlayerTwo  = User::factory()->create();
    $latestPlayerOne = User::factory()->create();
    $latestPlayerTwo = User::factory()->create();

    $event->users()->attach([
        $olderPlayerOne->id,
        $olderPlayerTwo->id,
        $latestPlayerOne->id,
        $latestPlayerTwo->id,
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
        'name'     => 'Group 1',
    ]);

    $olderGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $olderPlayerOne->id,
        'player_two_id' => $olderPlayerTwo->id,
        'created_at'    => Carbon::create(2026, 2, 27, 18, 45, 0),
        'started_at'    => Carbon::create(2026, 2, 27, 18, 45, 0),
        'finished_at'   => Carbon::create(2026, 2, 27, 18, 58, 0),
    ]);

    GameSet::factory()->create([
        'game_id'          => $olderGame->id,
        'player_one_id'    => $olderPlayerOne->id,
        'player_two_id'    => $olderPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 4,
    ]);
    GameSet::factory()->create([
        'game_id'          => $olderGame->id,
        'player_one_id'    => $olderPlayerOne->id,
        'player_two_id'    => $olderPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);

    $latestGame = Game::factory()->create([
        'event_id'         => $event->id,
        'round_id'         => $round->id,
        'group_id'         => $group->id,
        'best_of'          => 2,
        'player_one_id'    => $latestPlayerOne->id,
        'player_two_id'    => $latestPlayerTwo->id,
        'duration_seconds' => 1020,
        'created_at'       => Carbon::create(2026, 2, 27, 19, 20, 0),
        'started_at'       => Carbon::create(2026, 2, 27, 19, 20, 0),
        'finished_at'      => Carbon::create(2026, 2, 27, 19, 37, 0),
    ]);

    GameSet::factory()->create([
        'game_id'          => $latestGame->id,
        'player_one_id'    => $latestPlayerOne->id,
        'player_two_id'    => $latestPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);
    GameSet::factory()->create([
        'game_id'          => $latestGame->id,
        'player_one_id'    => $latestPlayerOne->id,
        'player_two_id'    => $latestPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    Livewire::test('tv.group-match', ['groupNumber' => 1])
        ->assertSee(tvShortName($latestPlayerOne))
        ->assertSee(tvShortName($latestPlayerTwo))
        ->assertSee('ZAVRŠENO')
        ->assertSee('17:00')
        ->assertDontSee(tvShortName($olderPlayerOne))
        ->assertDontSee(tvShortName($olderPlayerTwo));
});

test('tv group match component falls back to started_at to now when game is started without finished_at and without duration', function () {
    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
        'name'     => 'Group 1',
    ]);

    Game::factory()->create([
        'event_id'         => $event->id,
        'round_id'         => $round->id,
        'group_id'         => $group->id,
        'best_of'          => 2,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'duration_seconds' => null,
        'created_at'       => Carbon::create(2026, 2, 27, 19, 50, 0),
        'started_at'       => Carbon::create(2026, 2, 27, 19, 50, 0),
        'finished_at'      => null,
    ]);

    Carbon::setTestNow(Carbon::create(2026, 2, 27, 19, 57, 0));

    Livewire::test('tv.group-match', ['groupNumber' => 1])
        ->assertSee(tvShortName($playerOne))
        ->assertSee(tvShortName($playerTwo))
        ->assertSee('7:00');

    Carbon::setTestNow();
});

test('tv group match component prefers waiting game over finished game when no live game exists', function () {
    $event             = Event::factory()->create();
    $finishedPlayerOne = User::factory()->create();
    $finishedPlayerTwo = User::factory()->create();
    $waitingPlayerOne  = User::factory()->create();
    $waitingPlayerTwo  = User::factory()->create();

    $event->users()->attach([
        $finishedPlayerOne->id,
        $finishedPlayerTwo->id,
        $waitingPlayerOne->id,
        $waitingPlayerTwo->id,
    ]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
        'name'     => 'Group 1',
    ]);

    $finishedGame = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $finishedPlayerOne->id,
        'player_two_id' => $finishedPlayerTwo->id,
        'created_at'    => Carbon::create(2026, 2, 27, 18, 45, 0),
        'started_at'    => Carbon::create(2026, 2, 27, 18, 45, 0),
        'finished_at'   => Carbon::create(2026, 2, 27, 18, 58, 0),
    ]);

    GameSet::factory()->create([
        'game_id'          => $finishedGame->id,
        'player_one_id'    => $finishedPlayerOne->id,
        'player_two_id'    => $finishedPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 6,
    ]);
    GameSet::factory()->create([
        'game_id'          => $finishedGame->id,
        'player_one_id'    => $finishedPlayerOne->id,
        'player_two_id'    => $finishedPlayerTwo->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $waitingPlayerOne->id,
        'player_two_id' => $waitingPlayerTwo->id,
        'created_at'    => Carbon::create(2026, 2, 27, 19, 20, 0),
        'started_at'    => null,
        'finished_at'   => null,
    ]);

    Livewire::test('tv.group-match', ['groupNumber' => 1])
        ->assertSee(tvShortName($waitingPlayerOne))
        ->assertSee(tvShortName($waitingPlayerTwo))
        ->assertSee('NA ČEKANJU')
        ->assertDontSee(tvShortName($finishedPlayerOne))
        ->assertDontSee(tvShortName($finishedPlayerTwo));
});

test('tv group match component shows current set points from latest game log', function () {
    $event     = Event::factory()->create();
    $playerOne = User::factory()->create();
    $playerTwo = User::factory()->create();

    $event->users()->attach([$playerOne->id, $playerTwo->id]);

    $round = Round::factory()->create([
        'event_id' => $event->id,
        'number'   => 1,
        'name'     => 'Round 1',
    ]);
    $group = Group::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'number'   => 1,
        'name'     => 'Group 1',
    ]);

    $game = Game::factory()->create([
        'event_id'      => $event->id,
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'best_of'       => 2,
        'player_one_id' => $playerOne->id,
        'player_two_id' => $playerTwo->id,
        'started_at'    => Carbon::create(2026, 2, 27, 20, 0, 0),
        'finished_at'   => null,
    ]);

    GameLog::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'sequence'         => 1,
        'type'             => GameLogType::Score,
        'side'             => GameLogSide::Left,
        'player_one_score' => 5,
        'player_two_score' => 2,
    ]);

    GameLog::factory()->create([
        'game_id'          => $game->id,
        'player_one_id'    => $playerOne->id,
        'player_two_id'    => $playerTwo->id,
        'sequence'         => 2,
        'type'             => GameLogType::Score,
        'side'             => GameLogSide::Right,
        'player_one_score' => 21,
        'player_two_score' => 17,
    ]);

    Livewire::test('tv.group-match', ['groupNumber' => 1])
        ->assertSee(tvShortName($playerOne))
        ->assertSee(tvShortName($playerTwo))
        ->assertSee('21')
        ->assertSee('17')
        ->assertSee('UŽIVO');
});
