<?php

namespace App\Models;

use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    public const ALLOWED_BEST_OF_VALUES = [1, 2, 3, 5];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'round_id',
        'group_id',
        'best_of',
        'player_one_sets',
        'player_two_sets',
        'winner_id',
        'is_draw',
        'started_at',
        'finished_at',
        'duration_seconds',
        'player_one_id',
        'player_two_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            if (! $game->round_id && $game->group_id) {
                $game->round_id = $game->group?->round_id;
            }

            if ($game->started_at && $game->finished_at) {
                $duration = $game->started_at->diffInSeconds($game->finished_at);
                $game->duration_seconds = max(0, $duration);
            }

            if (! in_array($game->best_of, self::ALLOWED_BEST_OF_VALUES, true)) {
                throw new InvalidArgumentException('Game best_of must be one of: 1, 2, 3, 5.');
            }

            if ($game->player_one_id && $game->player_two_id && $game->player_one_id === $game->player_two_id) {
                throw new InvalidArgumentException('Game players must be different.');
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'best_of' => 'integer',
            'player_one_sets' => 'integer',
            'player_two_sets' => 'integer',
            'winner_id' => 'integer',
            'is_draw' => 'boolean',
            'round_id' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_one_id');
    }

    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_two_id');
    }

    public function sets(): HasMany
    {
        return $this->hasMany(GameSet::class);
    }

    public function gameLogs(): HasMany
    {
        return $this->hasMany(GameLog::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $sets
     */
    public static function determineWinnerIdFromSetScores(
        array $sets,
        int $bestOf,
        ?int $playerOneId,
        ?int $playerTwoId
    ): ?int {
        $result = self::determineMatchResultFromSetScores($sets, $bestOf, $playerOneId, $playerTwoId);

        return $result['winner_id'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sets
     * @return array{
     *     is_complete: bool,
     *     is_draw: bool,
     *     winner_id: int|null,
     *     player_one_wins: int,
     *     player_two_wins: int
     * }
     */
    public static function determineMatchResultFromSetScores(
        array $sets,
        int $bestOf,
        ?int $playerOneId,
        ?int $playerTwoId
    ): array {
        if (! in_array($bestOf, self::ALLOWED_BEST_OF_VALUES, true)) {
            return [
                'is_complete' => false,
                'is_draw' => false,
                'winner_id' => null,
                'player_one_wins' => 0,
                'player_two_wins' => 0,
            ];
        }

        if (! $playerOneId || ! $playerTwoId) {
            return [
                'is_complete' => false,
                'is_draw' => false,
                'winner_id' => null,
                'player_one_wins' => 0,
                'player_two_wins' => 0,
            ];
        }

        $wins = [
            $playerOneId => 0,
            $playerTwoId => 0,
        ];
        $completedSets = 0;

        foreach ($sets as $set) {
            if ($completedSets >= $bestOf) {
                break;
            }

            $playerOneScore = data_get($set, 'player_one_score');
            $playerTwoScore = data_get($set, 'player_two_score');

            if (! is_numeric($playerOneScore) || ! is_numeric($playerTwoScore)) {
                continue;
            }

            $playerOneScore = (int) $playerOneScore;
            $playerTwoScore = (int) $playerTwoScore;

            if ($playerOneScore === $playerTwoScore) {
                continue;
            }

            $maxScore = max($playerOneScore, $playerTwoScore);
            $minScore = min($playerOneScore, $playerTwoScore);
            $difference = $maxScore - $minScore;

            if ($maxScore < 11 || $difference < 2) {
                continue;
            }

            if ($maxScore > 11 && $minScore < 10) {
                continue;
            }

            $winnerId = $playerOneScore > $playerTwoScore ? $playerOneId : $playerTwoId;
            $wins[$winnerId] += 1;
            $completedSets += 1;
        }

        if ($completedSets < $bestOf) {
            return [
                'is_complete' => false,
                'is_draw' => false,
                'winner_id' => null,
                'player_one_wins' => $wins[$playerOneId],
                'player_two_wins' => $wins[$playerTwoId],
            ];
        }

        if ($wins[$playerOneId] === $wins[$playerTwoId]) {
            return [
                'is_complete' => true,
                'is_draw' => true,
                'winner_id' => null,
                'player_one_wins' => $wins[$playerOneId],
                'player_two_wins' => $wins[$playerTwoId],
            ];
        }

        $winnerId = $wins[$playerOneId] > $wins[$playerTwoId] ? $playerOneId : $playerTwoId;

        return [
            'is_complete' => true,
            'is_draw' => false,
            'winner_id' => $winnerId,
            'player_one_wins' => $wins[$playerOneId],
            'player_two_wins' => $wins[$playerTwoId],
        ];
    }
}
