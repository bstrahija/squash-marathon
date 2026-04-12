<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasMedia, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(function (): string {
            return trim("{$this->first_name} {$this->last_name}");
        });
    }

    protected function shortName(): Attribute
    {
        return Attribute::get(function (): string {
            $fullName = trim($this->full_name);

            if ($fullName === '') {
                return 'Igrac';
            }

            $parts = preg_split('/\s+/u', $fullName) ?: [];
            $firstName = $parts[0] ?? '';

            if ($firstName === '') {
                return $fullName;
            }

            $firstInitial = mb_substr($firstName, 0, 1);
            $lastName = trim(implode(' ', array_slice($parts, 1)));

            if ($lastName === '') {
                return sprintf('%s.', $firstInitial);
            }

            return sprintf('%s. %s', $firstInitial, $lastName);
        });
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function (): string {
            $parts = preg_split('/\s+/u', trim($this->full_name)) ?: [];
            $initials = collect($parts)
                ->filter()
                ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8'))
                ->take(2)
                ->join('');

            return $initials !== '' ? $initials : '—';
        });
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return true;
        }

        return $this->hasRole(RoleName::Admin->value);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 200, 200);
    }

    public function avatarUrl(string $conversion = ''): string
    {
        $conversionUrl = $this->getFirstMediaUrl('avatar', $conversion);

        if (filled($conversionUrl)) {
            return $conversionUrl;
        }

        $originalUrl = $this->getFirstMediaUrl('avatar');

        if (filled($originalUrl)) {
            return $originalUrl;
        }

        return asset('images/placeholder-avatar.svg');
    }

    public function getFallbackMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if ($collectionName !== 'avatar') {
            return '';
        }

        return asset('images/placeholder-avatar.svg');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }

    public function rounds(): BelongsToMany
    {
        return $this->belongsToMany(Round::class)->withTimestamps();
    }
}
