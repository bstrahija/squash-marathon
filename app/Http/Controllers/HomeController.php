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
                'leaderboard' => [],
                'timeline' => [],
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
                    'losses' => 0,
                    'games' => 0,
                    'last_game_at' => null,
                ],
            ];
        });

        foreach ($games as $game) {
            $winnerId = Game::determineWinnerIdFromSetScores(
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

            if (! $winnerId) {
                continue;
            }

            foreach ([$game->player_one_id, $game->player_two_id] as $playerId) {
                if (! $stats->has($playerId)) {
                    continue;
                }

                $row = $stats->get($playerId);
                $row['games'] += 1;

                if ($playerId === $winnerId) {
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
                'losses' => $row['losses'],
                'games' => $row['games'],
            ])
            ->values()
            ->all();

        $leaderboard = $stats
            ->values()
            ->map(fn (array $row): array => [
                'name' => $row['player']->full_name,
                'wins' => $row['wins'],
                'losses' => $row['losses'],
                'points' => ($row['wins'] * 2) + $row['losses'],
                'last_game_at' => $row['last_game_at'],
            ])
            ->sort(function (array $left, array $right): int {
                if ($left['points'] !== $right['points']) {
                    return $right['points'] <=> $left['points'];
                }

                if ($left['wins'] !== $right['wins']) {
                    return $right['wins'] <=> $left['wins'];
                }

                $leftTime = $left['last_game_at']?->timestamp ?? 0;
                $rightTime = $right['last_game_at']?->timestamp ?? 0;

                return $rightTime <=> $leftTime;
            })
            ->values()
            ->all();

        $timeline = $games
            ->filter(function ($game) use ($stats): bool {
                if (! $stats->has($game->player_one_id) || ! $stats->has($game->player_two_id)) {
                    return false;
                }

                $winnerId = Game::determineWinnerIdFromSetScores(
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

                return $winnerId !== null;
            })
            ->map(function ($game): array {
                $scores = $game->sets
                    ->filter(fn ($set): bool => filled($set->player_one_score) && filled($set->player_two_score))
                    ->map(fn ($set): string => "{$set->player_one_score}-{$set->player_two_score}")
                    ->implode(', ');

                return [
                    'time' => $game->created_at,
                    'game' => $game->playerOne->full_name.' vs '.$game->playerTwo->full_name,
                    'score' => $scores !== '' ? $scores : '—',
                ];
            })
            ->sortByDesc('time')
            ->take(24)
            ->values()
            ->all();

        return view('home', [
            'participants' => $participants,
            'leaderboard' => $leaderboard,
            'timeline' => $timeline,
        ]);
    }
}
