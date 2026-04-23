<?php

namespace App\Actions;

use App\Models\GameSchedule;
use App\Models\Round;
use Illuminate\Support\Facades\DB;

class CreateRoundGameScheduleAction
{
    public function execute(Round $round): int
    {
        $round->loadMissing(['groups.users']);

        $groups = $round->groups
            ->sortBy('number')
            ->values();

        if ($groups->isEmpty()) {
            GameSchedule::query()->where('round_id', $round->id)->delete();

            return 0;
        }

        $matchesByGroupAndRound = [];
        $maxRoundIndex          = -1;

        foreach ($groups as $group) {
            $playerIds = $group->users
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (count($playerIds) < 2) {
                $matchesByGroupAndRound[(int) $group->id] = [];

                continue;
            }

            $roundRobinRounds = $this->buildDoubleRoundRobinRounds($playerIds);

            $matchesByGroupAndRound[(int) $group->id] = $roundRobinRounds;
            $maxRoundIndex                            = max($maxRoundIndex, count($roundRobinRounds) - 1);
        }

        $now  = now();
        $rows = [];

        for ($roundIndex = 0; $roundIndex <= $maxRoundIndex; $roundIndex++) {
            foreach ($groups as $group) {
                $groupId = (int) $group->id;
                $matches = $matchesByGroupAndRound[$groupId][$roundIndex] ?? [];

                foreach ($matches as $match) {
                    $rows[] = [
                        'game_id'       => null,
                        'player_one_id' => $match['player_one_id'],
                        'player_two_id' => $match['player_two_id'],
                        'group_id'      => $groupId,
                        'round_id'      => (int) $round->id,
                        'starts_at'     => null,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        }

        return DB::transaction(function () use ($round, $rows): int {
            GameSchedule::query()
                ->where('round_id', $round->id)
                ->delete();

            if ($rows === []) {
                return 0;
            }

            GameSchedule::query()->insert($rows);

            return count($rows);
        });
    }

    /**
     * @param  array<int, int>  $playerIds
     * @return array<int, array<int, array{player_one_id: int, player_two_id: int}>>
     */
    private function buildDoubleRoundRobinRounds(array $playerIds): array
    {
        sort($playerIds);

        $participants = $playerIds;
        $hasBye       = count($participants) % 2 === 1;

        if ($hasBye) {
            $participants[] = null;
        }

        $participantCount = count($participants);

        if ($participantCount < 2) {
            return [];
        }

        $halfSize       = (int) ($participantCount / 2);
        $roundsPerLeg   = $participantCount - 1;
        $firstLegRounds = [];

        for ($roundIndex = 0; $roundIndex < $roundsPerLeg; $roundIndex++) {
            $matches = [];

            for ($index = 0; $index < $halfSize; $index++) {
                $left  = $participants[$index];
                $right = $participants[$participantCount - 1 - $index];

                if (! is_int($left) || ! is_int($right)) {
                    continue;
                }

                $matches[] = [
                    'player_one_id' => $left,
                    'player_two_id' => $right,
                ];
            }

            $firstLegRounds[] = $matches;
            $participants     = $this->rotateParticipants($participants);
        }

        $secondLegRounds = collect($firstLegRounds)
            ->map(fn (array $matches): array => array_map(
                fn (array $match): array => [
                    'player_one_id' => $match['player_two_id'],
                    'player_two_id' => $match['player_one_id'],
                ],
                $matches,
            ))
            ->all();

        return array_merge($firstLegRounds, $secondLegRounds);
    }

    /**
     * @param  array<int, int|null>  $participants
     * @return array<int, int|null>
     */
    private function rotateParticipants(array $participants): array
    {
        $fixed = array_shift($participants);

        if ($fixed === null || $participants === []) {
            return [];
        }

        $last = array_pop($participants);
        array_unshift($participants, $last);

        return array_merge([$fixed], $participants);
    }
}
