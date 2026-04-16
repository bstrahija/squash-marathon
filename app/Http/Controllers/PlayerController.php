<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Illuminate\Contracts\View\View;

class PlayerController extends Controller
{
    public function show(User $user): View
    {
        $event = $this->resolveCurrentEvent();

        $matches = Game::query()
            ->with(['group', 'round', 'playerOne', 'playerTwo', 'sets' => fn ($query) => $query->orderBy('created_at')])
            ->when($event, fn ($query) => $query->where('event_id', $event->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->where(fn ($query) => $query
                ->where('player_one_id', $user->id)
                ->orWhere('player_two_id', $user->id)
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $allEventMatches = Game::query()
            ->with(['sets'])
            ->when($event, fn ($query) => $query->where('event_id', $event->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->where(fn ($query) => $query
                ->where('player_one_id', $user->id)
                ->orWhere('player_two_id', $user->id)
            )
            ->get();

        $wins      = 0;
        $draws     = 0;
        $losses    = 0;
        $completed = 0;

        foreach ($allEventMatches as $game) {
            $result = Game::determineMatchResultFromSetScores(
                $game->sets
                    ->map(
                        fn ($set): array => [
                            'player_one_score' => $set->player_one_score,
                            'player_two_score' => $set->player_two_score,
                        ],
                    )
                    ->all(),
                $game->best_of,
                $game->player_one_id,
                $game->player_two_id,
            );

            if (! $result['is_complete']) {
                continue;
            }

            $completed++;

            if ($result['is_draw']) {
                $draws++;

                continue;
            }

            if (($result['winner_id'] ?? null) === $user->id) {
                $wins++;
            } else {
                $losses++;
            }
        }

        $inProgress = $allEventMatches->filter(fn (Game $game): bool => filled($game->started_at) && blank($game->finished_at))->count();

        return view('player-show', [
            'player'  => $user,
            'event'   => $event,
            'matches' => $matches,
            'stats'   => [
                'completed'   => $completed,
                'wins'        => $wins,
                'draws'       => $draws,
                'losses'      => $losses,
                'points'      => ($wins * 3) + ($draws * 2) + $losses,
                'in_progress' => $inProgress,
            ],
        ]);
    }

    private function resolveCurrentEvent(): ?Event
    {
        return Event::current();
    }
}
