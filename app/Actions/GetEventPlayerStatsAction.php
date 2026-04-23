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
     * @return Collection<int, array{player: User, wins: int, draws: int, losses: int, games: int, sets_won: int, sets_lost: int, points_scored: int, points_allowed: int, duration_seconds: int, last_game_at: Carbon|null}>
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
                    'player'           => $user,
                    'wins'             => 0,
                    'draws'            => 0,
                    'losses'           => 0,
                    'games'            => 0,
                    'sets_won'         => 0,
                    'sets_lost'        => 0,
                    'points_scored'    => 0,
                    'points_allowed'   => 0,
                    'duration_seconds' => 0,
                    'last_game_at'     => null,
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

                $isPlayerOne = (int) $playerId === (int) $game->player_one_id;

                $row['sets_won'] += $isPlayerOne ? $result['player_one_wins'] : $result['player_two_wins'];
                $row['sets_lost'] += $isPlayerOne ? $result['player_two_wins'] : $result['player_one_wins'];

                foreach ($game->sets as $set) {
                    if (! is_numeric($set->player_one_score) || ! is_numeric($set->player_two_score)) {
                        continue;
                    }

                    $playerOneScore = (int) $set->player_one_score;
                    $playerTwoScore = (int) $set->player_two_score;

                    $row['points_scored'] += $isPlayerOne ? $playerOneScore : $playerTwoScore;
                    $row['points_allowed'] += $isPlayerOne ? $playerTwoScore : $playerOneScore;
                }

                $row['duration_seconds'] += (int) ($game->duration_seconds ?? 0);

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
