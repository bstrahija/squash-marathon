<?php

namespace App\Livewire\Concerns;

use App\Enums\RoleName;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait HasGroupPlayerManagement
{
    public ?int $eventId = null;

    public string $eventName = '—';

    /**
     * @var array<int, int|string>
     */
    public array $groupOnePlayerIds = [];

    /**
     * @var array<int, int|string>
     */
    public array $groupTwoPlayerIds = [];

    public ?int $groupOnePlayerToAdd = null;

    public ?int $groupTwoPlayerToAdd = null;

    /**
     * The round number used to look up the previous round.
     * Implemented by the consuming component.
     */
    abstract protected function previousRoundNumber(): int;

    #[Computed]
    public function canManageRounds(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole(RoleName::Admin->value);
    }

    #[Computed]
    public function eventPlayers(): Collection
    {
        if (! $this->eventId) {
            return collect();
        }

        return User::query()
            ->whereHas('events', fn ($query) => $query->where('events.id', $this->eventId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function eventPlayersById(): Collection
    {
        return $this->eventPlayers->keyBy('id');
    }

    #[Computed]
    public function availablePlayers(): Collection
    {
        $selectedPlayerIds = collect($this->normalizePlayerIds($this->groupOnePlayerIds))
            ->merge($this->normalizePlayerIds($this->groupTwoPlayerIds))
            ->unique();

        return $this->eventPlayers
            ->reject(fn (User $player): bool => $selectedPlayerIds->contains($player->id))
            ->values();
    }

    #[Computed]
    public function groupOnePlayers(): Collection
    {
        return collect($this->normalizePlayerIds($this->groupOnePlayerIds))
            ->map(fn (int $playerId): ?User => $this->eventPlayersById->get($playerId))
            ->filter()
            ->values();
    }

    #[Computed]
    public function groupTwoPlayers(): Collection
    {
        return collect($this->normalizePlayerIds($this->groupTwoPlayerIds))
            ->map(fn (int $playerId): ?User => $this->eventPlayersById->get($playerId))
            ->filter()
            ->values();
    }

    #[Computed]
    public function previousRound(): ?Round
    {
        if (! $this->eventId) {
            return null;
        }

        return Round::previousForEvent($this->eventId, $this->previousRoundNumber());
    }

    #[Computed]
    public function hasPreviousRound(): bool
    {
        return $this->previousRound !== null;
    }

    public function updatedGroupOnePlayerToAdd(?int $playerId): void
    {
        if (! $playerId) {
            return;
        }

        $this->addPlayerToGroup(1);
    }

    public function updatedGroupTwoPlayerToAdd(?int $playerId): void
    {
        if (! $playerId) {
            return;
        }

        $this->addPlayerToGroup(2);
    }

    public function addPlayerToGroup(int $groupNumber): void
    {
        if (! $this->canManageRounds) {
            abort(403);
        }

        $groupProperty  = $this->groupPropertyForNumber($groupNumber);
        $pickerProperty = $this->pickerPropertyForNumber($groupNumber);

        if (! $groupProperty || ! $pickerProperty) {
            return;
        }

        $playerId = (int) ($this->{$pickerProperty} ?? 0);

        if ($playerId < 1) {
            return;
        }

        if (! $this->eventPlayersById->has($playerId)) {
            $this->{$pickerProperty} = null;

            return;
        }

        $groupOneIds = collect($this->normalizePlayerIds($this->groupOnePlayerIds));
        $groupTwoIds = collect($this->normalizePlayerIds($this->groupTwoPlayerIds));

        if ($groupOneIds->contains($playerId) || $groupTwoIds->contains($playerId)) {
            $this->addError('groupTwoPlayerIds', 'Igrač može biti samo u jednoj grupi.');
            $this->{$pickerProperty} = null;

            return;
        }

        if ($groupProperty === 'groupOnePlayerIds') {
            $this->groupOnePlayerIds = $groupOneIds->push($playerId)->unique()->values()->all();
        }

        if ($groupProperty === 'groupTwoPlayerIds') {
            $this->groupTwoPlayerIds = $groupTwoIds->push($playerId)->unique()->values()->all();
        }

        $this->{$pickerProperty} = null;

        $otherPickerProperty = $groupNumber === 1 ? 'groupTwoPlayerToAdd' : 'groupOnePlayerToAdd';

        if ((int) ($this->{$otherPickerProperty} ?? 0) === $playerId) {
            $this->{$otherPickerProperty} = null;
        }

        $this->resetValidation(['groupOnePlayerIds', 'groupOnePlayerIds.*', 'groupTwoPlayerIds', 'groupTwoPlayerIds.*']);
    }

    public function removePlayerFromGroup(int $groupNumber, int $playerId): void
    {
        if (! $this->canManageRounds) {
            abort(403);
        }

        $groupProperty = $this->groupPropertyForNumber($groupNumber);

        if (! $groupProperty) {
            return;
        }

        $currentIds = collect($this->normalizePlayerIds($this->{$groupProperty}))
            ->reject(fn (int $id): bool => $id === $playerId)
            ->values()
            ->all();

        $this->{$groupProperty} = $currentIds;
    }

    public function seedRandomGroups(): void
    {
        if (! $this->canManageRounds) {
            abort(403);
        }

        [$groupOnePlayerIds, $groupTwoPlayerIds] = Round::splitRandomPlayers($this->eventPlayers);

        $this->groupOnePlayerIds   = $groupOnePlayerIds;
        $this->groupTwoPlayerIds   = $groupTwoPlayerIds;
        $this->groupOnePlayerToAdd = null;
        $this->groupTwoPlayerToAdd = null;

        $this->resetValidation(['groupOnePlayerIds', 'groupOnePlayerIds.*', 'groupTwoPlayerIds', 'groupTwoPlayerIds.*']);
    }

    public function seedGroupsFromPreviousRoundPoints(): void
    {
        if (! $this->canManageRounds) {
            abort(403);
        }

        $previousRound = $this->previousRound;

        if (! $previousRound) {
            return;
        }

        [$groupOnePlayerIds, $groupTwoPlayerIds] = Round::splitPlayersFromPreviousRoundByPoints($previousRound, $this->eventPlayersById);

        $this->groupOnePlayerIds   = $groupOnePlayerIds;
        $this->groupTwoPlayerIds   = $groupTwoPlayerIds;
        $this->groupOnePlayerToAdd = null;
        $this->groupTwoPlayerToAdd = null;

        $this->resetValidation(['groupOnePlayerIds', 'groupOnePlayerIds.*', 'groupTwoPlayerIds', 'groupTwoPlayerIds.*']);
    }

    protected function normalizePlayerIds(array $playerIds): array
    {
        return collect($playerIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function groupPropertyForNumber(int $groupNumber): ?string
    {
        return match ($groupNumber) {
            1       => 'groupOnePlayerIds',
            2       => 'groupTwoPlayerIds',
            default => null,
        };
    }

    protected function pickerPropertyForNumber(int $groupNumber): ?string
    {
        return match ($groupNumber) {
            1       => 'groupOnePlayerToAdd',
            2       => 'groupTwoPlayerToAdd',
            default => null,
        };
    }
}
