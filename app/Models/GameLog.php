<?php

namespace App\Models;

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameLog extends Model
{
    /** @use HasFactory<\Database\Factories\GameLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'player_one_id',
        'player_two_id',
        'sequence',
        'type',
        'side',
        'serving_player_id',
        'serving_side',
        'player_one_score',
        'player_two_score',
        'player_one_sets',
        'player_two_sets',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'type' => GameLogType::class,
            'side' => GameLogSide::class,
            'serving_player_id' => 'integer',
            'serving_side' => GameLogSide::class,
            'player_one_score' => 'integer',
            'player_two_score' => 'integer',
            'player_one_sets' => 'integer',
            'player_two_sets' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function playerOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_one_id');
    }

    public function playerTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_two_id');
    }

    public function servingPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'serving_player_id');
    }
}
