<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Group;
use App\Models\Round;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public ?int $eventId = null;

    public ?int $groupId = null;

    public ?int $playerOneId = null;

    public ?int $playerTwoId = null;

    public string $eventName = '—';

    public function mount(): void
    {
        $event = Event::query()->latest('start_at')->first();

        if (!$event) {
            return;
        }

        $this->eventId = $event->id;
        $this->eventName = $event->name;

        $activeRoundId = $this->activeRoundId();

        if (!$activeRoundId) {
            return;
        }

        $this->groupId = Group::query()->where('event_id', $event->id)->where('round_id', $activeRoundId)->orderBy('number')->value('id');
    }

    public function updatedGroupId(): void
    {
        $this->playerOneId = null;
        $this->playerTwoId = null;
    }

    public function createMatch(): void
    {
        $validated = $this->validate(
            [
                'eventId' => ['required', 'integer', Rule::exists('events', 'id')],
                'groupId' => ['required', 'integer', Rule::exists('groups', 'id')->where(fn($query) => $query->where('event_id', $this->eventId)->where('round_id', $this->activeRoundId()))],
                'playerOneId' => ['required', 'integer', Rule::exists('group_user', 'user_id')->where(fn($query) => $query->where('group_id', $this->groupId))],
                'playerTwoId' => ['required', 'integer', Rule::exists('group_user', 'user_id')->where(fn($query) => $query->where('group_id', $this->groupId)), 'different:playerOneId'],
            ],
            [
                'groupId.required' => 'Odaberite grupu.',
                'groupId.exists' => 'Odaberite grupu iz aktivne runde.',
                'playerOneId.required' => 'Odaberite prvog igrača.',
                'playerOneId.exists' => 'Prvi igrač mora biti iz odabrane grupe.',
                'playerTwoId.required' => 'Odaberite drugog igrača.',
                'playerTwoId.exists' => 'Drugi igrač mora biti iz odabrane grupe.',
                'playerTwoId.different' => 'Igrači moraju biti različiti.',
            ],
        );

        $group = Group::query()->whereKey($validated['groupId'])->where('event_id', $validated['eventId'])->firstOrFail();

        $game = Game::query()->create([
            'event_id' => (int) $validated['eventId'],
            'round_id' => $group->round_id,
            'group_id' => $group->id,
            'best_of' => 2,
            'player_one_id' => (int) $validated['playerOneId'],
            'player_two_id' => (int) $validated['playerTwoId'],
        ]);

        $this->redirectRoute('matches.score', ['game' => $game->id]);
    }

    /**
     * @return array<int, string>
     */
    public function groupOptions(): array
    {
        if (!$this->eventId) {
            return [];
        }

        $activeRoundId = $this->activeRoundId();

        if (!$activeRoundId) {
            return [];
        }

        return Group::query()
            ->with('round')
            ->where('event_id', $this->eventId)
            ->where('round_id', $activeRoundId)
            ->orderBy('number')
            ->get()
            ->mapWithKeys(
                fn(Group $group): array => [
                    $group->id => sprintf('%s - %s', $group->round?->name ?? 'Runda', $group->name),
                ],
            )
            ->all();
    }

    public function activeRoundId(): ?int
    {
        if (!$this->eventId) {
            return null;
        }

        return Round::query()->where('event_id', $this->eventId)->where('is_active', true)->orderByDesc('number')->orderByDesc('id')->value('id');
    }

    /**
     * @return array<int, string>
     */
    public function playerOptions(): array
    {
        if (!$this->groupId) {
            return [];
        }

        $group = Group::query()->find($this->groupId);

        if (!$group) {
            return [];
        }

        return $group->users()->orderBy('first_name')->orderBy('last_name')->get()->mapWithKeys(fn(User $user): array => [$user->id => $user->full_name])->all();
    }
};
?>

<div class="flex min-h-screen w-full items-center justify-center px-6 py-8">
    <section class="w-full max-w-3xl rounded-3xl border border-border bg-card p-6 shadow-sm">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Novi meč</p>
            <h1 class="font-display mt-2 text-2xl font-semibold text-foreground">Kreiranje meča</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Event je automatski odabran prema zadnjem unesenom događaju.
            </p>
        </div>

        <form class="space-y-5" wire:submit="createMatch">
            <input type="hidden" wire:model="eventId" />

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                    Event
                </label>
                <div
                    class="rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm font-medium text-foreground">
                    {{ $eventName }}
                </div>
            </div>

            <div>
                <label for="group_id"
                    class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                    Grupa
                </label>
                <select id="group_id" wire:model.live="groupId"
                    class="w-full rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none">
                    <option value="">Odaberi grupu</option>
                    @foreach ($this->groupOptions() as $groupOptionId => $groupName)
                        <option value="{{ $groupOptionId }}">{{ $groupName }}</option>
                    @endforeach
                </select>
                @error('groupId')
                    <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                @enderror
            </div>

            @php($playerOptions = $this->playerOptions())

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="player_one_id"
                        class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        Igrač 1
                    </label>
                    <select id="player_one_id" wire:key="player-one-{{ $groupId ?? 'none' }}" wire:model="playerOneId"
                        @disabled(!$groupId || empty($playerOptions))
                        class="w-full rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none">
                        <option value="">Odaberi igrača</option>
                        @foreach ($playerOptions as $playerId => $playerName)
                            <option value="{{ $playerId }}">{{ $playerName }}</option>
                        @endforeach
                    </select>
                    @error('playerOneId')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="player_two_id"
                        class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        Igrač 2
                    </label>
                    <select id="player_two_id" wire:key="player-two-{{ $groupId ?? 'none' }}" wire:model="playerTwoId"
                        @disabled(!$groupId || empty($playerOptions))
                        class="w-full rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none">
                        <option value="">Odaberi igrača</option>
                        @foreach ($playerOptions as $playerId => $playerName)
                            <option value="{{ $playerId }}">{{ $playerName }}</option>
                        @endforeach
                    </select>
                    @error('playerTwoId')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit"
                class="rounded-full bg-primary px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-primary-foreground shadow-sm transition hover:-translate-y-0.5">
                Kreiraj meč
            </button>
        </form>
    </section>
</div>
