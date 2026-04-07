<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $event = Event::query()->latest('start_at')->first();

        if (! $event) {
            return view('home', [
                'participants' => [],
            ]);
        }

        $players = $event->users()->get();

        if ($players->isEmpty()) {
            $players = User::role(RoleName::Player->value)->get();
        }

        if ($players->isEmpty()) {
            $players = User::query()->get();
        }

        $games = Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $event->id)
            ->get();

        $stats = $players->mapWithKeys(function (User $user): array {
            return [
                $user->id => [
                    'player' => $user,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'games' => 0,
                    'last_game_at' => null,
                ],
            ];
        });

        foreach ($games as $game) {
            $result = Game::determineMatchResultFromSetScores(
                $game->sets
                    ->map(fn ($set): array => [
                        'player_one_score' => $set->player_one_score,
                        'player_two_score' => $set->player_two_score,
                    ])
                    ->all(),
                $game->best_of,
                $game->player_one_id,
                $game->player_two_id
            );

            if (! $result['is_complete']) {
                continue;
            }

            foreach ([$game->player_one_id, $game->player_two_id] as $playerId) {
                if (! $stats->has($playerId)) {
                    continue;
                }

                $row = $stats->get($playerId);
                $row['games'] += 1;

                if ($result['is_draw']) {
                    $row['draws'] += 1;
                } elseif ($playerId === $result['winner_id']) {
                    $row['wins'] += 1;
                } else {
                    $row['losses'] += 1;
                }

                if (! $row['last_game_at'] || $row['last_game_at']->lt($game->created_at)) {
                    $row['last_game_at'] = $game->created_at;
                }

                $stats->put($playerId, $row);
            }
        }

        $participants = $stats
            ->values()
            ->sortBy(fn (array $row): string => $row['player']->last_name)
            ->map(fn (array $row): array => [
                'name' => $row['player']->full_name,
                'wins' => $row['wins'],
                'draws' => $row['draws'],
                'losses' => $row['losses'],
                'games' => $row['games'],
            ])
            ->values()
            ->all();

        return view('home', [
            'participants' => $participants,
        ]);
    }
}
