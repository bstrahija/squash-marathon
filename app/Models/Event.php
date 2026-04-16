<?php

namespace App\Models;

use App\Enums\RoleName;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, InteractsWithMedia;

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if ($event->start_at && $event->end_at && $event->end_at->lessThanOrEqualTo($event->start_at)) {
                throw new InvalidArgumentException('Event end_at must be after start_at.');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'start_at',
        'end_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at'   => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 480, 270)
            ->nonQueued();
    }

    public function photoUrl(string $conversion = ''): string
    {
        return $this->getFirstMediaUrl('photo', $conversion)
            ?: asset('images/placeholder-event.svg');
    }

    public function getFallbackMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if ($collectionName !== 'photo') {
            return '';
        }

        return asset('images/placeholder-event.svg');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public static function current(): ?self
    {
        return self::query()->latest('start_at')->first();
    }

    /**
     * @return Collection<int, User>
     */
    public function resolvedPlayers(): Collection
    {
        $players = $this->users()->get();

        if ($players->isEmpty()) {
            $players = User::role(RoleName::Player->value)->get();
        }

        if ($players->isEmpty()) {
            $players = User::query()->get();
        }

        return $players;
    }

    /**
     * @return Collection<int, array{
     *     player: User,
     *     wins: int,
     *     draws: int,
     *     losses: int,
     *     games: int,
     *     points: int,
     *     last_game_at: Carbon|null
     * }>
     */
    public function leaderboardStats(): Collection
    {
        $players = $this->resolvedPlayers();

        $games = $this->games()
            ->with(['sets'])
            ->get();

        $stats = $players->mapWithKeys(function (User $user): array {
            return [
                $user->id => [
                    'player'       => $user,
                    'wins'         => 0,
                    'draws'        => 0,
                    'losses'       => 0,
                    'games'        => 0,
                    'points'       => 0,
                    'last_game_at' => null,
                ],
            ];
        });

        foreach ($games as $game) {
            $result = Game::determineMatchResultFromSetCollection(
                $game->sets,
                (int) $game->best_of,
                $game->player_one_id,
                $game->player_two_id,
            );

            if (! $result['is_complete']) {
                continue;
            }

            foreach ([$game->player_one_id, $game->player_two_id] as $playerId) {
                if (! $stats->has($playerId)) {
                    continue;
                }

                $row = $stats->get($playerId);
                $row['games'] += 1;

                if ($result['is_draw']) {
                    $row['draws'] += 1;
                    $row['points'] += 2;
                } elseif ($playerId === $result['winner_id']) {
                    $row['wins'] += 1;
                    $row['points'] += 3;
                } else {
                    $row['losses'] += 1;
                    $row['points'] += 1;
                }

                if (! $row['last_game_at'] || $row['last_game_at']->lt($game->created_at)) {
                    $row['last_game_at'] = $game->created_at;
                }

                $stats->put($playerId, $row);
            }
        }

        return $stats->values();
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     short_name: string,
     *     profile_url: string,
     *     wins: int,
     *     draws: int,
     *     losses: int,
     *     games: int,
     *     points: int,
     *     last_game_at: Carbon|null
     * }>
     */
    public function leaderboardRows(): Collection
    {
        return $this->leaderboardStats()
            ->map(fn (array $row): array => [
                'id'           => $row['player']->id,
                'name'         => $row['player']->full_name,
                'short_name'   => $row['player']->short_name,
                'profile_url'  => route('players.show', ['user' => $row['player']->id]),
                'wins'         => $row['wins'],
                'draws'        => $row['draws'],
                'losses'       => $row['losses'],
                'games'        => $row['games'],
                'points'       => $row['points'],
                'last_game_at' => $row['last_game_at'],
            ])
            ->sort(function (array $left, array $right): int {
                if ($left['points'] !== $right['points']) {
                    return $right['points'] <=> $left['points'];
                }

                if ($left['wins'] !== $right['wins']) {
                    return $right['wins'] <=> $left['wins'];
                }

                $leftTime  = $left['last_game_at']?->timestamp ?? 0;
                $rightTime = $right['last_game_at']?->timestamp ?? 0;

                return $rightTime <=> $leftTime;
            })
            ->values();
    }
}
