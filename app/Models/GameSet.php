<?php

namespace App\Models;

use Database\Factories\GameSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class GameSet extends Model
{
    /** @use HasFactory<GameSetFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sets';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'round_id',
        'group_id',
        'player_one_id',
        'player_two_id',
        'winner_id',
        'player_one_score',
        'player_two_score',
        'started_at',
        'finished_at',
        'duration_seconds',
    ];

    protected static function booted(): void
    {
        static::saving(function (GameSet $set): void {
            if (! $set->round_id && $set->game_id) {
                $set->round_id = $set->game?->round_id;
            }

            if (! $set->group_id && $set->game_id) {
                $set->group_id = $set->game?->group_id;
            }

            if ($set->started_at && $set->finished_at) {
                $duration = $set->started_at->diffInSeconds($set->finished_at);
                $set->duration_seconds = max(0, $duration);
            }

            $playerOneScore = $set->player_one_score;
            $playerTwoScore = $set->player_two_score;

            if (blank($playerOneScore) && blank($playerTwoScore)) {
                return;
            }

            if (blank($playerOneScore) || blank($playerTwoScore)) {
                throw new InvalidArgumentException('Set scores must be provided for both players.');
            }

            if ($playerOneScore < 0) {
                throw new InvalidArgumentException('Set player_one_score must be 0 or higher.');
            }

            if ($playerTwoScore < 0) {
                throw new InvalidArgumentException('Set player_two_score must be 0 or higher.');
            }

            $maxScore = max($playerOneScore, $playerTwoScore);
            $minScore = min($playerOneScore, $playerTwoScore);
            $difference = $maxScore - $minScore;

            if ($maxScore < 11) {
                throw new InvalidArgumentException('Set winner must have at least 11 points.');
            }

            if ($difference < 2) {
                throw new InvalidArgumentException('Set winner must lead by at least 2 points.');
            }

            if ($maxScore > 11 && $minScore < 10) {
                throw new InvalidArgumentException('Set scores must reach 10-10 before extending past 11.');
            }

            if ($set->player_one_id && $set->player_two_id && $set->player_one_id === $set->player_two_id) {
                throw new InvalidArgumentException('Set players must be different.');
            }

            if ($set->player_one_id && $set->player_two_id) {
                $set->winner_id = $playerOneScore > $playerTwoScore
                    ? $set->player_one_id
                    : $set->player_two_id;
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
            'round_id' => 'integer',
            'group_id' => 'integer',
            'player_one_score' => 'integer',
            'player_two_score' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
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

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
}
