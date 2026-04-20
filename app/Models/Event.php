<?php

namespace App\Models;

use App\Enums\RoleName;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * Resolve the participants for this event, falling back progressively.
     *
     * @return Collection<int, User>
     */
    public function resolveParticipants(): Collection
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
     * Return the most recent completed games for this event, sorted newest first.
     *
     * @return Collection<int, Game>
     */
    public function latestCompletedGames(int $limit = 20): Collection
    {
        return Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $this->id)
            ->latest('id')
            ->get()
            ->filter(fn (Game $game): bool => $game->resultFromSets()['is_complete'])
            ->take($limit)
            ->values();
    }

    public static function current(): ?self
    {
        return self::query()->latest('start_at')->first();
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
}
