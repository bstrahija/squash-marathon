<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetEventPlayerStatsAction
{
    /**
     * Calculate wins/draws/losses/games stats for every participant of an event.
     *
     * @return Collection<int, array{player: User, wins: int, draws: int, losses: int, games: int, last_game_at: Carbon|null}>
     */
    public function execute(Event $event): Collection
    {
        $players = $event->resolveParticipants();

        $games = Game::query()
            ->with(['sets'])
            ->where('event_id', $event->id)
            ->get();

        $stats = $players->mapWithKeys(function (User $user): array {
            return [
                $user->id => [
                    'player'       => $user,
                    'wins'         => 0,
                    'draws'        => 0,
                    'losses'       => 0,
                    'games'        => 0,
                    'last_game_at' => null,
                ],
            ];
        });

        foreach ($games as $game) {
            $result = $game->resultFromSets();

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

        return $stats;
    }
}
