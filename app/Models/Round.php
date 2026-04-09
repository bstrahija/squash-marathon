<?php

namespace App\Models;

use Database\Factories\RoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Round extends Model
{
    /** @use HasFactory<RoundFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'number',
        'name',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function sets(): HasMany
    {
        return $this->hasMany(Set::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function nextNumberForEvent(int $eventId): int
    {
        $latestRoundNumber = (int) self::query()->where('event_id', $eventId)->max('number');

        return $latestRoundNumber + 1;
    }

    public static function previousForEvent(int $eventId, int $beforeRoundNumber): ?self
    {
        if ($beforeRoundNumber < 2) {
            return null;
        }

        return self::query()
            ->with(['groups.users'])
            ->where('event_id', $eventId)
            ->where('number', '<', $beforeRoundNumber)
            ->orderByDesc('number')
            ->first();
    }

    /**
     * @param  Collection<int, User>  $eventPlayers
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    public static function splitRandomPlayers(Collection $eventPlayers): array
    {
        $shuffledPlayerIds = $eventPlayers->pluck('id')->map(fn ($id): int => (int) $id)->shuffle()->values();

        $splitIndex = (int) ceil($shuffledPlayerIds->count() / 2);

        return [
            $shuffledPlayerIds->take($splitIndex)->values()->all(),
            $shuffledPlayerIds->slice($splitIndex)->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, User>  $eventPlayersById
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    public static function splitPlayersFromPreviousRoundByPoints(self $previousRound, Collection $eventPlayersById): array
    {
        $previousGroups = $previousRound->groups
            ->whereIn('number', [1, 2])
            ->sortBy('number')
            ->values();

        if ($previousGroups->isEmpty()) {
            return [[], []];
        }

        $gamesByGroup = Game::query()
            ->with('sets')
            ->where('round_id', $previousRound->id)
            ->whereIn('group_id', $previousGroups->pluck('id')->all())
            ->get()
            ->groupBy('group_id');

        $nextGroupOnePlayerIds = collect();
        $nextGroupTwoPlayerIds = collect();

        foreach ($previousGroups as $group) {
            $standings = self::groupStandingsByPoints(
                $group,
                $gamesByGroup->get($group->id, collect()),
                $eventPlayersById,
            );

            $splitIndex = (int) ceil($standings->count() / 2);

            $nextGroupOnePlayerIds = $nextGroupOnePlayerIds->merge($standings->take($splitIndex)->pluck('player_id'));
            $nextGroupTwoPlayerIds = $nextGroupTwoPlayerIds->merge($standings->slice($splitIndex)->pluck('player_id'));
        }

        $groupOnePlayerIds = $nextGroupOnePlayerIds
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $groupTwoPlayerIds = $nextGroupTwoPlayerIds
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $playerId): bool => $groupOnePlayerIds->contains($playerId))
            ->unique()
            ->values();

        return [$groupOnePlayerIds->all(), $groupTwoPlayerIds->all()];
    }

    /**
     * @param  array<int, int>  $groupOnePlayerIds
     * @param  array<int, int>  $groupTwoPlayerIds
     */
    public static function createForEventWithGroups(
        int $eventId,
        array $groupOnePlayerIds,
        array $groupTwoPlayerIds,
    ): self {
        return DB::transaction(function () use ($eventId, $groupOnePlayerIds, $groupTwoPlayerIds): self {
            $latestRoundNumber = (int) self::query()->where('event_id', $eventId)->lockForUpdate()->max('number');

            $roundNumber = $latestRoundNumber + 1;

            self::query()->where('event_id', $eventId)->update(['is_active' => false]);

            $round = self::query()->create([
                'event_id' => $eventId,
                'number' => $roundNumber,
                'name' => "Runda {$roundNumber}",
                'is_active' => true,
            ]);

            $groupOne = Group::query()->create([
                'event_id' => $eventId,
                'round_id' => $round->id,
                'number' => 1,
                'name' => 'Grupa 1',
            ]);

            $groupTwo = Group::query()->create([
                'event_id' => $eventId,
                'round_id' => $round->id,
                'number' => 2,
                'name' => 'Grupa 2',
            ]);

            $groupOne->users()->sync($groupOnePlayerIds);
            $groupTwo->users()->sync($groupTwoPlayerIds);

            $round->users()->sync(
                collect($groupOnePlayerIds)
                    ->merge($groupTwoPlayerIds)
                    ->unique()
                    ->values()
                    ->all(),
            );

            return $round;
        });
    }

    /**
     * @param  array<int, int>  $groupOnePlayerIds
     * @param  array<int, int>  $groupTwoPlayerIds
     */
    public static function updateForEventWithGroups(
        int $roundId,
        int $eventId,
        string $roundName,
        array $groupOnePlayerIds,
        array $groupTwoPlayerIds,
    ): self {
        return DB::transaction(function () use ($roundId, $eventId, $roundName, $groupOnePlayerIds, $groupTwoPlayerIds): self {
            $round = self::query()
                ->whereKey($roundId)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            $round->update(['name' => $roundName]);

            $groupOne = Group::query()->firstOrCreate(
                [
                    'event_id' => $eventId,
                    'round_id' => $round->id,
                    'number' => 1,
                ],
                [
                    'name' => 'Grupa 1',
                ],
            );

            $groupTwo = Group::query()->firstOrCreate(
                [
                    'event_id' => $eventId,
                    'round_id' => $round->id,
                    'number' => 2,
                ],
                [
                    'name' => 'Grupa 2',
                ],
            );

            $groupOne->users()->sync($groupOnePlayerIds);
            $groupTwo->users()->sync($groupTwoPlayerIds);

            $round->users()->sync(
                collect($groupOnePlayerIds)
                    ->merge($groupTwoPlayerIds)
                    ->unique()
                    ->values()
                    ->all(),
            );

            return $round;
        });
    }

    /**
     * @param  Collection<int, Game>  $games
     * @param  Collection<int, User>  $eventPlayersById
     * @return Collection<int, array{player_id: int, points: int, wins: int, draws: int, losses: int, sort_name: string}>
     */
    private static function groupStandingsByPoints(Group $group, Collection $games, Collection $eventPlayersById): Collection
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
                    'points' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
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
            $loserId = $winnerId === $playerOneId ? $playerTwoId : $playerOneId;

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

                if ($left['draws'] !== $right['draws']) {
                    return $right['draws'] <=> $left['draws'];
                }

                if ($left['losses'] !== $right['losses']) {
                    return $left['losses'] <=> $right['losses'];
                }

                return strcmp($left['sort_name'], $right['sort_name']);
            })
            ->values();
    }
}
