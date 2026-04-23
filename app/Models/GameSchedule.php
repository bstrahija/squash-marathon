<?php

namespace App\Models;

use Database\Factories\GameScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSchedule extends Model
{
    /** @use HasFactory<GameScheduleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'player_one_id',
        'player_two_id',
        'group_id',
        'round_id',
        'starts_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'game_id'       => 'integer',
            'player_one_id' => 'integer',
            'player_two_id' => 'integer',
            'group_id'      => 'integer',
            'round_id'      => 'integer',
            'starts_at'     => 'datetime',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
