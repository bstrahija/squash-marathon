<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?int $eventId = null;

    public string $eventName = '—';

    public int $nextRoundNumber = 1;

    public string $roundName = 'Grupa 1';

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

    public function mount(): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        $event = $this->resolveCurrentEvent();

        if (!$event) {
            return;
        }

        $this->eventId = $event->id;
        $this->eventName = $event->name;
        $this->hydrateRoundDraft();
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

    public function saveRound(): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        if (!$this->eventId) {
            $this->addError('eventId', 'Nema aktivnog eventa za kreiranje runde.');

            return;
        }

        $this->groupOnePlayerIds = $this->normalizePlayerIds($this->groupOnePlayerIds);
        $this->groupTwoPlayerIds = $this->normalizePlayerIds($this->groupTwoPlayerIds);

        $validated = $this->validate(
            [
                'groupOnePlayerIds' => ['required', 'array', 'min:1'],
                'groupOnePlayerIds.*' => ['integer', Rule::exists('event_user', 'user_id')->where(fn($query) => $query->where('event_id', $this->eventId))],
                'groupTwoPlayerIds' => ['required', 'array', 'min:1'],
                'groupTwoPlayerIds.*' => ['integer', Rule::exists('event_user', 'user_id')->where(fn($query) => $query->where('event_id', $this->eventId))],
            ],
            [
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

        DB::transaction(function () use ($groupOneIds, $groupTwoIds): void {
            $latestRoundNumber = (int) Round::query()->where('event_id', $this->eventId)->lockForUpdate()->max('number');

            $roundNumber = $latestRoundNumber + 1;

            Round::query()
                ->where('event_id', $this->eventId)
                ->update(['is_active' => false]);

            $round = Round::query()->create([
                'event_id' => $this->eventId,
                'number' => $roundNumber,
                'name' => "Grupa {$roundNumber}",
                'is_active' => true,
            ]);

            $groupOne = Group::query()->create([
                'event_id' => $this->eventId,
                'round_id' => $round->id,
                'number' => 1,
                'name' => 'Grupa 1',
            ]);

            $groupTwo = Group::query()->create([
                'event_id' => $this->eventId,
                'round_id' => $round->id,
                'number' => 2,
                'name' => 'Grupa 2',
            ]);

            $groupOne->users()->sync($groupOneIds->all());
            $groupTwo->users()->sync($groupTwoIds->all());

            $round->users()->sync($groupOneIds->merge($groupTwoIds)->unique()->values()->all());
        });

        session()->flash('status', 'Nova runda je uspješno kreirana.');

        $this->redirectRoute('rounds.index');
    }

    protected function resolveCurrentEvent(): ?Event
    {
        $now = now();

        return Event::query()->where('start_at', '<=', $now)->where('end_at', '>=', $now)->latest('start_at')->first() ?? Event::query()->latest('start_at')->first();
    }

    protected function hydrateRoundDraft(): void
    {
        if (!$this->eventId) {
            $this->nextRoundNumber = 1;
            $this->roundName = 'Grupa 1';

            return;
        }

        $latestRoundNumber = (int) Round::query()->where('event_id', $this->eventId)->max('number');

        $this->nextRoundNumber = $latestRoundNumber + 1;
        $this->roundName = "Grupa {$this->nextRoundNumber}";
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
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Nova runda</p>
            <h1 class="font-display mt-2 text-3xl font-semibold text-foreground">Kreiranje runde</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Event: <span class="font-semibold text-foreground">{{ $eventName }}</span>
            </p>
            <p class="text-sm text-muted-foreground">
                Naziv runde se generira automatski: <span
                    class="font-semibold text-foreground">{{ $roundName }}</span>
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

    @if ($errors->has('eventId'))
        <div
            class="mb-4 rounded-2xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
            {{ $errors->first('eventId') }}
        </div>
    @endif

    @if ($this->eventPlayers->isEmpty())
        <div class="rounded-2xl border border-border/70 bg-background/70 px-4 py-5 text-sm text-muted-foreground">
            Nema prijavljenih igrača za ovaj event.
        </div>
    @else
        @php($availablePlayers = $this->availablePlayers)

        <form class="space-y-6" wire:submit="saveRound">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-border/70 bg-background/70 p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold text-foreground">Grupa 1</h2>
                        <span
                            class="rounded-full border border-border px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ $this->groupOnePlayers->count() }} igrača
                        </span>
                    </div>

                    <div class="mb-4">
                        <label for="group-one-player-to-add"
                            class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                            Dodaj igrača
                        </label>
                        <select id="group-one-player-to-add" wire:model.live="groupOnePlayerToAdd"
                            class="w-full rounded-2xl border border-border/70 bg-background/80 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none"
                            wire:loading.attr="disabled" wire:target="updatedGroupOnePlayerToAdd,saveRound">
                            <option value="">Odaberi igrača</option>
                            @foreach ($availablePlayers as $player)
                                <option value="{{ $player->id }}">{{ $player->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($this->groupOnePlayers->isEmpty())
                        <div
                            class="rounded-2xl border border-dashed border-border/70 px-4 py-6 text-center text-sm text-muted-foreground">
                            Još nema igrača u grupi 1.
                        </div>
                    @else
                        <ul class="space-y-2">
                            @foreach ($this->groupOnePlayers as $player)
                                <li wire:key="group-one-player-{{ $player->id }}"
                                    class="flex items-center justify-between gap-3 rounded-xl border border-border/70 bg-card px-3 py-2.5 text-sm">
                                    <span class="font-medium text-foreground">{{ $player->full_name }}</span>
                                    <button type="button" wire:click="removePlayerFromGroup(1, {{ $player->id }})"
                                        wire:loading.attr="disabled" wire:target="removePlayerFromGroup,saveRound"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:border-red-400/60 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="1.8" />
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @error('groupOnePlayerIds')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                    @error('groupOnePlayerIds.*')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </section>

                <section class="rounded-2xl border border-border/70 bg-background/70 p-4">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold text-foreground">Grupa 2</h2>
                        <span
                            class="rounded-full border border-border px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ $this->groupTwoPlayers->count() }} igrača
                        </span>
                    </div>

                    <div class="mb-4">
                        <label for="group-two-player-to-add"
                            class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                            Dodaj igrača
                        </label>
                        <select id="group-two-player-to-add" wire:model.live="groupTwoPlayerToAdd"
                            class="w-full rounded-2xl border border-border/70 bg-background/80 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none"
                            wire:loading.attr="disabled" wire:target="updatedGroupTwoPlayerToAdd,saveRound">
                            <option value="">Odaberi igrača</option>
                            @foreach ($availablePlayers as $player)
                                <option value="{{ $player->id }}">{{ $player->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($this->groupTwoPlayers->isEmpty())
                        <div
                            class="rounded-2xl border border-dashed border-border/70 px-4 py-6 text-center text-sm text-muted-foreground">
                            Još nema igrača u grupi 2.
                        </div>
                    @else
                        <ul class="space-y-2">
                            @foreach ($this->groupTwoPlayers as $player)
                                <li wire:key="group-two-player-{{ $player->id }}"
                                    class="flex items-center justify-between gap-3 rounded-xl border border-border/70 bg-card px-3 py-2.5 text-sm">
                                    <span class="font-medium text-foreground">{{ $player->full_name }}</span>
                                    <button type="button" wire:click="removePlayerFromGroup(2, {{ $player->id }})"
                                        wire:loading.attr="disabled" wire:target="removePlayerFromGroup,saveRound"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:border-red-400/60 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"
                                                stroke-linejoin="round" stroke-width="1.8" />
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @error('groupTwoPlayerIds')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                    @error('groupTwoPlayerIds.*')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </section>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button type="submit"
                    class="rounded-full bg-primary px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-primary-foreground shadow-sm transition hover:-translate-y-0.5">
                    Započni rundu
                </button>
            </div>
        </form>
    @endif
</div>
