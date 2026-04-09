<?php

use App\Enums\RoleName;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?int $roundId = null;

    public ?int $eventId = null;

    public int $roundNumber = 0;

    public string $eventName = '—';

    public string $roundName = '';

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

    public function mount(int $roundId): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        $this->roundId = $roundId;

        $round = Round::query()
            ->with(['event', 'groups.users'])
            ->whereKey($roundId)
            ->firstOrFail();

        $this->eventId = $round->event_id;
        $this->roundNumber = $round->number;
        $this->eventName = $round->event?->name ?? '—';
        $this->roundName = $round->name;

        $groupOne = $round->groups->firstWhere('number', 1);
        $groupTwo = $round->groups->firstWhere('number', 2);

        $this->groupOnePlayerIds = $groupOne ? $groupOne->users->pluck('id')->map(fn(int $id): int => (int) $id)->values()->all() : [];

        $this->groupTwoPlayerIds = $groupTwo ? $groupTwo->users->pluck('id')->map(fn(int $id): int => (int) $id)->values()->all() : [];
    }

    #[Computed]
    public function canManageRounds(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole(RoleName::Admin->value);
    }

    #[Computed]
    public function eventPlayers(): Collection
    {
        if (!$this->eventId) {
            return collect();
        }

        return User::query()->whereHas('events', fn($query) => $query->where('events.id', $this->eventId))->orderBy('first_name')->orderBy('last_name')->get();
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

        return $this->eventPlayers->reject(fn(User $player): bool => $selectedPlayerIds->contains($player->id))->values();
    }

    #[Computed]
    public function groupOnePlayers(): Collection
    {
        return collect($this->normalizePlayerIds($this->groupOnePlayerIds))
            ->map(fn(int $playerId): ?User => $this->eventPlayersById->get($playerId))
            ->filter()
            ->values();
    }

    #[Computed]
    public function groupTwoPlayers(): Collection
    {
        return collect($this->normalizePlayerIds($this->groupTwoPlayerIds))
            ->map(fn(int $playerId): ?User => $this->eventPlayersById->get($playerId))
            ->filter()
            ->values();
    }

    #[Computed]
    public function previousRound(): ?Round
    {
        if (!$this->eventId || $this->roundNumber < 2) {
            return null;
        }

        return Round::previousForEvent($this->eventId, $this->roundNumber);
    }

    #[Computed]
    public function hasPreviousRound(): bool
    {
        return $this->previousRound !== null;
    }

    public function updatedGroupOnePlayerToAdd(?int $playerId): void
    {
        if (!$playerId) {
            return;
        }

        $this->addPlayerToGroup(1);
    }

    public function updatedGroupTwoPlayerToAdd(?int $playerId): void
    {
        if (!$playerId) {
            return;
        }

        $this->addPlayerToGroup(2);
    }

    public function addPlayerToGroup(int $groupNumber): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        $groupProperty = $this->groupPropertyForNumber($groupNumber);
        $pickerProperty = $this->pickerPropertyForNumber($groupNumber);

        if (!$groupProperty || !$pickerProperty) {
            return;
        }

        $playerId = (int) ($this->{$pickerProperty} ?? 0);

        if ($playerId < 1) {
            return;
        }

        if (!$this->eventPlayersById->has($playerId)) {
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
        if (!$this->canManageRounds) {
            abort(403);
        }

        $groupProperty = $this->groupPropertyForNumber($groupNumber);

        if (!$groupProperty) {
            return;
        }

        $currentIds = collect($this->normalizePlayerIds($this->{$groupProperty}))
            ->reject(fn(int $id): bool => $id === $playerId)
            ->values()
            ->all();

        $this->{$groupProperty} = $currentIds;
    }

    public function seedRandomGroups(): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        [$groupOnePlayerIds, $groupTwoPlayerIds] = Round::splitRandomPlayers($this->eventPlayers);

        $this->groupOnePlayerIds = $groupOnePlayerIds;
        $this->groupTwoPlayerIds = $groupTwoPlayerIds;
        $this->groupOnePlayerToAdd = null;
        $this->groupTwoPlayerToAdd = null;

        $this->resetValidation(['groupOnePlayerIds', 'groupOnePlayerIds.*', 'groupTwoPlayerIds', 'groupTwoPlayerIds.*']);
    }

    public function seedGroupsFromPreviousRoundPoints(): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        $previousRound = $this->previousRound;

        if (!$previousRound) {
            return;
        }

        [$groupOnePlayerIds, $groupTwoPlayerIds] = Round::splitPlayersFromPreviousRoundByPoints($previousRound, $this->eventPlayersById);

        $this->groupOnePlayerIds = $groupOnePlayerIds;
        $this->groupTwoPlayerIds = $groupTwoPlayerIds;
        $this->groupOnePlayerToAdd = null;
        $this->groupTwoPlayerToAdd = null;

        $this->resetValidation(['groupOnePlayerIds', 'groupOnePlayerIds.*', 'groupTwoPlayerIds', 'groupTwoPlayerIds.*']);
    }

    public function saveRound(): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        if (!$this->roundId || !$this->eventId) {
            abort(404);
        }

        $this->groupOnePlayerIds = $this->normalizePlayerIds($this->groupOnePlayerIds);
        $this->groupTwoPlayerIds = $this->normalizePlayerIds($this->groupTwoPlayerIds);

        $validated = $this->validate(
            [
                'roundName' => ['required', 'string', 'max:255'],
                'groupOnePlayerIds' => ['required', 'array', 'min:1'],
                'groupOnePlayerIds.*' => ['integer', Rule::exists('event_user', 'user_id')->where(fn($query) => $query->where('event_id', $this->eventId))],
                'groupTwoPlayerIds' => ['required', 'array', 'min:1'],
                'groupTwoPlayerIds.*' => ['integer', Rule::exists('event_user', 'user_id')->where(fn($query) => $query->where('event_id', $this->eventId))],
            ],
            [
                'roundName.required' => 'Naziv runde je obavezan.',
                'roundName.max' => 'Naziv runde smije imati najviše 255 znakova.',
                'groupOnePlayerIds.required' => 'Odaberite igrače za grupu 1.',
                'groupOnePlayerIds.min' => 'Grupa 1 mora imati barem jednog igrača.',
                'groupOnePlayerIds.*.exists' => 'Igrači u grupi 1 moraju biti prijavljeni na event.',
                'groupTwoPlayerIds.required' => 'Odaberite igrače za grupu 2.',
                'groupTwoPlayerIds.min' => 'Grupa 2 mora imati barem jednog igrača.',
                'groupTwoPlayerIds.*.exists' => 'Igrači u grupi 2 moraju biti prijavljeni na event.',
            ],
        );

        $groupOneIds = collect($validated['groupOnePlayerIds'])->map(static fn($id): int => (int) $id)->unique()->values();

        $groupTwoIds = collect($validated['groupTwoPlayerIds'])->map(static fn($id): int => (int) $id)->unique()->values();

        if ($groupOneIds->intersect($groupTwoIds)->isNotEmpty()) {
            $this->addError('groupTwoPlayerIds', 'Igrač može biti samo u jednoj grupi.');

            return;
        }

        Round::updateForEventWithGroups((int) $this->roundId, (int) $this->eventId, trim($validated['roundName']), $groupOneIds->all(), $groupTwoIds->all());

        session()->flash('status', 'Runda je uspješno ažurirana.');

        $this->redirectRoute('rounds.index');
    }

    protected function normalizePlayerIds(array $playerIds): array
    {
        return collect($playerIds)->map(fn($id): int => (int) $id)->filter(fn(int $id): bool => $id > 0)->unique()->values()->all();
    }

    protected function groupPropertyForNumber(int $groupNumber): ?string
    {
        return match ($groupNumber) {
            1 => 'groupOnePlayerIds',
            2 => 'groupTwoPlayerIds',
            default => null,
        };
    }

    protected function pickerPropertyForNumber(int $groupNumber): ?string
    {
        return match ($groupNumber) {
            1 => 'groupOnePlayerToAdd',
            2 => 'groupTwoPlayerToAdd',
            default => null,
        };
    }
};
?>

<div class="rounded-3xl border border-border bg-card/80 p-6 shadow-sm">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Uredi rundu</p>
            <h1 class="font-display mt-2 text-3xl font-semibold text-foreground">
                Uređivanje runde #{{ $roundNumber }}
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Event: <span class="font-semibold text-foreground">{{ $eventName }}</span>
            </p>
        </div>

        <a href="{{ route('rounds.index') }}"
            class="rounded-full border border-border px-4 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground transition hover:border-foreground/40 hover:text-foreground">
            Natrag na runde
        </a>
    </div>

    @if (session('status'))
        <div
            class="mb-4 rounded-2xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form class="space-y-6" wire:submit="saveRound">
        <div>
            <label for="round_name"
                class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                Naziv runde
            </label>
            <input id="round_name" type="text" wire:model="roundName"
                class="w-full rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none" />
            @error('roundName')
                <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
            @enderror
        </div>

        @if ($this->eventPlayers->isEmpty())
            <div class="rounded-2xl border border-border/70 bg-background/70 px-4 py-5 text-sm text-muted-foreground">
                Nema prijavljenih igrača za ovaj event.
            </div>
        @else
            @php($availablePlayers = $this->availablePlayers)

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-rounds.group-player-picker title="Grupa 1" :group-number="1" :players="$this->groupOnePlayers" :available-players="$availablePlayers"
                    add-model="groupOnePlayerToAdd" update-method="updatedGroupOnePlayerToAdd"
                    error-key="groupOnePlayerIds" error-item-key="groupOnePlayerIds.*"
                    wire-key-prefix="round-edit-group-one" />

                <x-rounds.group-player-picker title="Grupa 2" :group-number="2" :players="$this->groupTwoPlayers" :available-players="$availablePlayers"
                    add-model="groupTwoPlayerToAdd" update-method="updatedGroupTwoPlayerToAdd"
                    error-key="groupTwoPlayerIds" error-item-key="groupTwoPlayerIds.*"
                    wire-key-prefix="round-edit-group-two" />
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-end gap-3">
            @if ($this->hasPreviousRound)
                <button type="button" wire:click="seedGroupsFromPreviousRoundPoints" wire:loading.attr="disabled"
                    wire:target="seedGroupsFromPreviousRoundPoints,seedRandomGroups,saveRound"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border text-foreground transition hover:border-foreground/40 disabled:cursor-not-allowed disabled:opacity-50"
                    title="Podijeli prema bodovima prethodne runde"
                    aria-label="Podijeli prema bodovima prethodne runde">
                    <x-heroicon-o-trophy class="h-4 w-4" />
                </button>
            @endif

            <button type="button" wire:click="seedRandomGroups" wire:loading.attr="disabled"
                wire:target="seedRandomGroups,saveRound"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border text-foreground transition hover:border-foreground/40 disabled:cursor-not-allowed disabled:opacity-50"
                title="Nasumično podijeli igrače" aria-label="Nasumično podijeli igrače">
                <x-heroicon-o-arrow-path class="h-4 w-4" />
            </button>

            <button type="submit"
                class="rounded-full bg-primary px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-primary-foreground shadow-sm transition hover:-translate-y-0.5">
                Spremi izmjene
            </button>
        </div>
    </form>
</div>
