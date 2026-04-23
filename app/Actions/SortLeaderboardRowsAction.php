<?php

namespace App\Actions;

class SortLeaderboardRowsAction
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function execute(array $rows, string $sortBy, string $sortDirection): array
    {
        $allowedColumns = ['points', 'wins', 'draws', 'losses', 'matches', 'sets_difference', 'points_difference', 'duration_seconds'];
        $sortBy         = in_array($sortBy, $allowedColumns, true) ? $sortBy : 'points';
        $sortDirection  = $sortDirection === 'asc' ? 'asc' : 'desc';

        return collect($rows)
            ->sort(function (array $left, array $right) use ($sortBy, $sortDirection): int {
                $result = $this->compareValues($left[$sortBy] ?? null, $right[$sortBy] ?? null);

                if ($sortDirection === 'desc') {
                    $result *= -1;
                }

                if ($result !== 0) {
                    return $result;
                }

                // Keep deterministic ordering when column values tie.
                $fallback = $this->compareValues($left['points'] ?? null, $right['points'] ?? null);

                if ($fallback !== 0) {
                    return $fallback * -1;
                }

                return $this->compareValues($left['name'] ?? null, $right['name'] ?? null);
            })
            ->values()
            ->all();
    }

    private function compareValues(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return (int) $left <=> (int) $right;
        }

        return mb_strtolower((string) $left) <=> mb_strtolower((string) $right);
    }
}
