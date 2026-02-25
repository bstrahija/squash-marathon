<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Game extends Model
{
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'best_of',
        'player_one_id',
        'player_two_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            if (! in_array($game->best_of, [1, 3, 5], true)) {
                throw new InvalidArgumentException('Game best_of must be 1, 3, or 5.');
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
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
        return $this->hasMany(Set::class);
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
        if (! in_array($bestOf, [1, 3, 5], true)) {
            return null;
        }

        if (! $playerOneId || ! $playerTwoId) {
            return null;
        }

        $targetWins = (int) ceil($bestOf / 2);
        $wins = [
            $playerOneId => 0,
            $playerTwoId => 0,
        ];

        foreach ($sets as $set) {
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

            if ($wins[$winnerId] >= $targetWins) {
                return $winnerId;
            }
        }

        return null;
    }
}
