<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\Set;
use App\Models\User;
use Illuminate\Support\Carbon;

test('player page renders sidebar profile and leaderboard', function () {
    $this->withoutVite();

    Carbon::setTestNow(Carbon::create(2026, 4, 24, 23, 15, 0));

    $event = Event::factory()->create([
        'name' => 'Maraton Travanj 2026',
        'start_at' => now()->copy()->subHours(4),
        'end_at' => now()->copy()->addHours(6),
    ]);

    $player = User::factory()->create([
        'first_name' => 'Marko',
        'last_name' => 'Maric',
    ]);
    $opponent = User::factory()->create();

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

    $game = Game::factory()->create([
        'event_id' => $event->id,
        'round_id' => $round->id,
        'group_id' => $group->id,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'started_at' => now()->copy()->subMinutes(20),
        'finished_at' => now()->copy()->subMinutes(1),
    ]);

    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'player_one_score' => 11,
        'player_two_score' => 7,
    ]);
    Set::factory()->create([
        'game_id' => $game->id,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
        'player_one_score' => 11,
        'player_two_score' => 8,
    ]);

    $response = $this->get(route('players.show', $player));

    $response->assertStatus(200);
    $response->assertSee($player->full_name);
    $response->assertSee('placeholder-avatar.svg');
    $response->assertSee($event->name);
    $response->assertSee('Poredak');
    $response->assertSee(route('matches.score', $game), false);

    Carbon::setTestNow();
});

test('player page lists only current event matches for selected player', function () {
    $this->withoutVite();

    Carbon::setTestNow(Carbon::create(2026, 4, 24, 23, 15, 0));

    $currentEvent = Event::factory()->create([
        'name' => 'Current Event',
        'start_at' => now()->copy()->subHours(2),
        'end_at' => now()->copy()->addHours(2),
    ]);
    $otherEvent = Event::factory()->create([
        'name' => 'Old Event',
        'start_at' => now()->copy()->subDays(5),
        'end_at' => now()->copy()->subDays(4),
    ]);

    $player = User::factory()->create();
    $opponent = User::factory()->create();
    $otherPlayer = User::factory()->create();

    $roundCurrent = Round::factory()->create([
        'event_id' => $currentEvent->id,
        'number' => 1,
    ]);
    $groupCurrent = Group::factory()->create([
        'event_id' => $currentEvent->id,
        'round_id' => $roundCurrent->id,
        'number' => 1,
    ]);

    $roundOther = Round::factory()->create([
        'event_id' => $otherEvent->id,
        'number' => 1,
    ]);
    $groupOther = Group::factory()->create([
        'event_id' => $otherEvent->id,
        'round_id' => $roundOther->id,
        'number' => 1,
    ]);

    $currentMatch = Game::factory()->create([
        'event_id' => $currentEvent->id,
        'round_id' => $roundCurrent->id,
        'group_id' => $groupCurrent->id,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
    ]);

    $otherEventMatch = Game::factory()->create([
        'event_id' => $otherEvent->id,
        'round_id' => $roundOther->id,
        'group_id' => $groupOther->id,
        'player_one_id' => $player->id,
        'player_two_id' => $opponent->id,
    ]);

    $otherPlayerMatch = Game::factory()->create([
        'event_id' => $currentEvent->id,
        'round_id' => $roundCurrent->id,
        'group_id' => $groupCurrent->id,
        'player_one_id' => $otherPlayer->id,
        'player_two_id' => $opponent->id,
    ]);

    $response = $this->get(route('players.show', $player));

    $response->assertSuccessful();
    $response->assertSee(route('matches.score', $currentMatch), false);
    $response->assertDontSee(route('matches.score', $otherEventMatch), false);
    $response->assertDontSee(route('matches.score', $otherPlayerMatch), false);

    Carbon::setTestNow();
});
