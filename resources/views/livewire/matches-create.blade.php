<?php

use App\Enums\RoleName;
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
        $event = Event::current();

        if (!$event) {
            return;
        }

        $this->eventId = $event->id;
        $this->eventName = $event->name;

        $activeRoundId = $this->activeRoundId();

        if (!$activeRoundId) {
            $this->redirectForMissingRound();

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
        $activeRoundId = $this->activeRoundId();

        if (!$activeRoundId) {
            $this->redirectForMissingRound();

            return;
        }

        $validated = $this->validate(
            [
                'eventId' => ['required', 'integer', Rule::exists('events', 'id')],
                'groupId' => ['required', 'integer', Rule::exists('groups', 'id')->where(fn($query) => $query->where('event_id', $this->eventId)->where('round_id', $activeRoundId))],
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

<div class="bg-card/80 shadow-sm p-6 border border-border rounded-3xl">
    <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
        <div>
            <p class="font-semibold text-muted-foreground text-xs uppercase tracking-[0.2em]">Novi meč</p>
            <h1 class="mt-2 font-display font-semibold text-foreground text-3xl">Kreiranje meča</h1>
            <p class="mt-2 text-muted-foreground text-sm">
                Event je automatski odabran prema zadnjem unesenom događaju.
            </p>
        </div>

        <a href="{{ route('matches.index') }}"
            class="px-4 py-2 border border-border hover:border-foreground/40 rounded-full font-semibold text-muted-foreground hover:text-foreground text-xs uppercase tracking-wide transition">
            Natrag na mečeve
        </a>
    </div>

    <form class="space-y-6" wire:submit="createMatch">
        <input type="hidden" wire:model="eventId" />

        <div>
            <label class="block mb-2 font-semibold text-muted-foreground text-xs uppercase tracking-[0.16em]">
                Event
            </label>
            <div
                class="bg-background/70 px-4 py-3 border border-border/70 rounded-2xl font-medium text-foreground text-sm">
                {{ $eventName }}
            </div>
        </div>

        <div>
            <label for="group_id"
                class="block mb-2 font-semibold text-muted-foreground text-xs uppercase tracking-[0.16em]">
                Grupa
            </label>
            <select id="group_id" wire:model.live="groupId"
                class="bg-background/70 px-4 py-3 border border-border/70 focus:border-foreground/40 rounded-2xl focus:outline-none w-full text-foreground text-sm">
                <option value="">Odaberi grupu</option>
                @foreach ($this->groupOptions() as $groupOptionId => $groupName)
                    <option value="{{ $groupOptionId }}">{{ $groupName }}</option>
                @endforeach
            </select>
            @error('groupId')
                <p class="mt-2 text-red-600 dark:text-red-300 text-xs">{{ $message }}</p>
            @enderror
        </div>

        @php($playerOptions = $this->playerOptions())

        <div class="gap-4 grid sm:grid-cols-2">
            <div>
                <label for="player_one_id"
                    class="block mb-2 font-semibold text-muted-foreground text-xs uppercase tracking-[0.16em]">
                    Igrač 1
                </label>
                <select id="player_one_id" wire:key="player-one-{{ $groupId ?? 'none' }}" wire:model="playerOneId"
                    @disabled(!$groupId || empty($playerOptions))
                    class="bg-background/70 px-4 py-3 border border-border/70 focus:border-foreground/40 rounded-2xl focus:outline-none w-full text-foreground text-sm">
                    <option value="">Odaberi igrača</option>
                    @foreach ($playerOptions as $playerId => $playerName)
                        <option value="{{ $playerId }}">{{ $playerName }}</option>
                    @endforeach
                </select>
                @error('playerOneId')
                    <p class="mt-2 text-red-600 dark:text-red-300 text-xs">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="player_two_id"
                    class="block mb-2 font-semibold text-muted-foreground text-xs uppercase tracking-[0.16em]">
                    Igrač 2
                </label>
                <select id="player_two_id" wire:key="player-two-{{ $groupId ?? 'none' }}" wire:model="playerTwoId"
                    @disabled(!$groupId || empty($playerOptions))
                    class="bg-background/70 px-4 py-3 border border-border/70 focus:border-foreground/40 rounded-2xl focus:outline-none w-full text-foreground text-sm">
                    <option value="">Odaberi igrača</option>
                    @foreach ($playerOptions as $playerId => $playerName)
                        <option value="{{ $playerId }}">{{ $playerName }}</option>
                    @endforeach
                </select>
                @error('playerTwoId')
                    <p class="mt-2 text-red-600 dark:text-red-300 text-xs">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit"
            class="bg-primary shadow-sm px-5 py-2.5 rounded-full font-semibold text-primary-foreground text-xs uppercase tracking-wide transition hover:-translate-y-0.5">
            Kreiraj meč
        </button>
    </form>
</div>
