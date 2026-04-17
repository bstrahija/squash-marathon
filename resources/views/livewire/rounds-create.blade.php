<?php

use App\Enums\RoleName;
use App\Livewire\Concerns\HasGroupPlayerManagement;
use App\Models\Event;
use App\Models\Round;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use HasGroupPlayerManagement;

    public string $redirectAfterCreate = 'rounds.index';

    public string $createMode = 'start';

    public int $nextRoundNumber = 1;

    public string $roundName = 'Runda 1';

    public function mount(): void
    {
        if (! $this->canManageRounds) {
            abort(403);
        }

        $this->resolveRedirectAfterCreate();
        $this->resolveCreateMode();

        $event = $this->resolveCurrentEvent();

        if (! $event) {
            return;
        }

        $this->eventId = $event->id;
        $this->eventName = $event->name;
        $this->hydrateRoundDraft();
        $this->resolveCreateMode();
    }

    /**
     * @return array{heading: string, submit: string}
     */
    #[Computed]
    public function modeText(): array
    {
        if ($this->createMode === 'finish') {
            return [
                'heading' => 'Završi rundu i kreiraj novu',
                'submit'  => 'Spremi rundu',
            ];
        }

        return [
            'heading' => 'Kreiranje prve runde',
            'submit'  => 'Započni rundu',
        ];
    }

    public function saveRound(): void
    {
        if (! $this->canManageRounds) {
            abort(403);
        }

        if (! $this->eventId) {
            $this->addError('eventId', 'Nema aktivnog eventa za kreiranje runde.');

            return;
        }

        $this->groupOnePlayerIds = $this->normalizePlayerIds($this->groupOnePlayerIds);
        $this->groupTwoPlayerIds = $this->normalizePlayerIds($this->groupTwoPlayerIds);

        $validated = $this->validate(
            [
                'groupOnePlayerIds'   => ['required', 'array', 'min:1'],
                'groupOnePlayerIds.*' => ['integer', Rule::exists('event_user', 'user_id')->where(fn ($query) => $query->where('event_id', $this->eventId))],
                'groupTwoPlayerIds'   => ['required', 'array', 'min:1'],
                'groupTwoPlayerIds.*' => ['integer', Rule::exists('event_user', 'user_id')->where(fn ($query) => $query->where('event_id', $this->eventId))],
            ],
            [
                'groupOnePlayerIds.required'   => 'Odaberite igrače za grupu 1.',
                'groupOnePlayerIds.min'         => 'Grupa 1 mora imati barem jednog igrača.',
                'groupOnePlayerIds.*.exists'    => 'Igrači u grupi 1 moraju biti prijavljeni na event.',
                'groupTwoPlayerIds.required'   => 'Odaberite igrače za grupu 2.',
                'groupTwoPlayerIds.min'         => 'Grupa 2 mora imati barem jednog igrača.',
                'groupTwoPlayerIds.*.exists'    => 'Igrači u grupi 2 moraju biti prijavljeni na event.',
            ],
        );

        $groupOneIds = collect($validated['groupOnePlayerIds'])->map(static fn ($id): int => (int) $id)->unique()->values();
        $groupTwoIds = collect($validated['groupTwoPlayerIds'])->map(static fn ($id): int => (int) $id)->unique()->values();

        if ($groupOneIds->intersect($groupTwoIds)->isNotEmpty()) {
            $this->addError('groupTwoPlayerIds', 'Igrač može biti samo u jednoj grupi.');

            return;
        }

        Round::createForEventWithGroups((int) $this->eventId, $groupOneIds->all(), $groupTwoIds->all());

        session()->flash('status', 'Nova runda je uspješno kreirana.');

        $this->redirectRoute($this->redirectAfterCreate);
    }

    protected function previousRoundNumber(): int
    {
        return $this->nextRoundNumber;
    }

    protected function resolveCurrentEvent(): ?Event
    {
        return Event::current();
    }

    protected function hydrateRoundDraft(): void
    {
        if (! $this->eventId) {
            $this->nextRoundNumber = 1;
            $this->roundName = 'Runda 1';

            return;
        }

        $this->nextRoundNumber = Round::nextNumberForEvent($this->eventId);
        $this->roundName = "Runda {$this->nextRoundNumber}";
    }

    protected function resolveRedirectAfterCreate(): void
    {
        $requestedRoute = request()->query('redirect');

        if (! is_string($requestedRoute)) {
            return;
        }

        if (! in_array($requestedRoute, ['matches.create'], true)) {
            return;
        }

        $this->redirectAfterCreate = $requestedRoute;
    }

    protected function resolveCreateMode(): void
    {
        $requestedMode = request()->query('mode');

        if (is_string($requestedMode) && in_array($requestedMode, ['start', 'finish'], true)) {
            $this->createMode = $requestedMode;

            return;
        }

        if (! $this->eventId) {
            $this->createMode = 'start';

            return;
        }

        $this->createMode = Round::query()->where('event_id', $this->eventId)->exists() ? 'finish' : 'start';
    }

    private function redirectForMissingRound(): void
    {
        $user = auth()->user();

        if ((bool) $user?->hasRole(RoleName::Admin->value)) {
            $this->redirectRoute('rounds.create', ['redirect' => 'matches.create']);

            return;
        }

        session()->flash('status', 'Nema aktivne runde. Obratite se administratoru.');
        $this->redirectRoute('matches.index');
    }
};
?>

<div class="rounded-3xl border border-border bg-card/80 p-6 shadow-sm">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        @php($modeText = $this->modeText)

        <div>
            <h1 class="font-display mt-2 text-3xl font-semibold text-foreground">
                {{ $modeText['heading'] }}
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
                <x-rounds.group-player-picker title="Grupa 1" :group-number="1" :players="$this->groupOnePlayers" :available-players="$availablePlayers"
                    add-model="groupOnePlayerToAdd" update-method="updatedGroupOnePlayerToAdd"
                    error-key="groupOnePlayerIds" error-item-key="groupOnePlayerIds.*"
                    wire-key-prefix="round-create-group-one" />

                <x-rounds.group-player-picker title="Grupa 2" :group-number="2" :players="$this->groupTwoPlayers" :available-players="$availablePlayers"
                    add-model="groupTwoPlayerToAdd" update-method="updatedGroupTwoPlayerToAdd"
                    error-key="groupTwoPlayerIds" error-item-key="groupTwoPlayerIds.*"
                    wire-key-prefix="round-create-group-two" />
            </div>

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
                    {{ $modeText['submit'] }}
                </button>
            </div>
        </form>
    @endif
</div>
