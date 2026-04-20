<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Collection;

class GetGroupStandingsAction
{
    /**
     * @return array{
     *     round: array{id: int, name: string, number: int}|null,
     *     group: array{id: int, name: string, number: int}|null,
     *     rows: array<int, array{id: int, name: string, profile_url: string|null, points: int, wins: int, draws: int, losses: int}>
     * }
     */
    public function execute(Event $event, int $groupNumber): array
    {
        $round = Round::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->orderByDesc('number')
            ->with(['groups.users'])
            ->first();

        if (! $round) {
            $round = Round::query()
                ->where('event_id', $event->id)
                ->orderByDesc('number')
                ->with(['groups.users'])
                ->first();
        }

        if (! $round) {
            return [
                'round' => null,
                'group' => null,
                'rows'  => [],
            ];
        }

        $group = $round->groups->firstWhere('number', $groupNumber);

        if (! $group) {
            return [
                'round' => [
                    'id'     => $round->id,
                    'name'   => $round->name,
                    'number' => $round->number,
                ],
                'group' => null,
                'rows'  => [],
            ];
        }

        $group->loadMissing('users');

        $eventPlayersById = $event->resolveParticipants()->keyBy('id');

        $games = Game::query()
            ->with('sets')
            ->where('event_id', $event->id)
            ->where('round_id', $round->id)
            ->where('group_id', $group->id)
            ->get();

        $standings = $this->groupStandingsByPoints($group, $games, $eventPlayersById);

        $rows = $standings
            ->map(function (array $row) use ($eventPlayersById): array {
                /** @var User|null $player */
                $player = $eventPlayersById->get($row['player_id']);

                return [
                    'id'          => $row['player_id'],
                    'name'        => $player?->short_name ?? '—',
                    'profile_url' => $player ? route('players.show', ['user' => $player->id]) : null,
                    'points'      => $row['points'],
                    'wins'        => $row['wins'],
                    'draws'       => $row['draws'],
                    'losses'      => $row['losses'],
                ];
            })
            ->values()
            ->all();

        return [
            'round' => [
                'id'     => $round->id,
                'name'   => $round->name,
                'number' => $round->number,
            ],
            'group' => [
                'id'     => $group->id,
                'name'   => $group->name,
                'number' => $group->number,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Collection<int, Game>  $games
     * @param  Collection<int, User>  $eventPlayersById
     * @return Collection<int, array{player_id: int, points: int, wins: int, draws: int, losses: int, sort_name: string}>
     */
    private function groupStandingsByPoints(Group $group, Collection $games, Collection $eventPlayersById): Collection
    {
        $groupPlayerIds = $group->users
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $eventPlayersById->has($id))
            ->values();

        $standings = $groupPlayerIds->mapWithKeys(function (int $playerId) use ($eventPlayersById): array {
            $player = $eventPlayersById->get($playerId);

            return [
                $playerId => [
                    'player_id' => $playerId,
                    'points'    => 0,
                    'wins'      => 0,
                    'draws'     => 0,
                    'losses'    => 0,
                    'sort_name' => mb_strtolower((string) ($player?->full_name ?? (string) $playerId)),
                ],
            ];
        });

        foreach ($games as $game) {
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

            $playerOneId = (int) ($game->player_one_id ?? 0);
            $playerTwoId = (int) ($game->player_two_id ?? 0);

            if (! $standings->has($playerOneId) || ! $standings->has($playerTwoId)) {
                continue;
            }

            if ($result['is_draw']) {
                foreach ([$playerOneId, $playerTwoId] as $playerId) {
                    $row = $standings->get($playerId);
                    $row['draws'] += 1;
                    $row['points'] += 2;
                    $standings->put($playerId, $row);
                }

                continue;
            }

            $winnerId = (int) ($result['winner_id'] ?? 0);
            $loserId  = $winnerId === $playerOneId ? $playerTwoId : $playerOneId;

            if (! $standings->has($winnerId) || ! $standings->has($loserId)) {
                continue;
            }

            $winnerRow = $standings->get($winnerId);
            $winnerRow['wins'] += 1;
            $winnerRow['points'] += 3;
            $standings->put($winnerId, $winnerRow);

            $loserRow = $standings->get($loserId);
            $loserRow['losses'] += 1;
            $loserRow['points'] += 1;
            $standings->put($loserId, $loserRow);
        }

        return $standings
            ->values()
            ->sort(function (array $left, array $right): int {
                if ($left['points'] !== $right['points']) {
                    return $right['points'] <=> $left['points'];
                }

                if ($left['wins'] !== $right['wins']) {
                    return $right['wins'] <=> $left['wins'];
                }

                return $left['sort_name'] <=> $right['sort_name'];
            })
            ->values();
    }
}
