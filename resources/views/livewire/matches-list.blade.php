<?php

use App\Enums\RoleName;
use App\Models\Game;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $confirmingDeletionId = null;

    public string $playerFilter = '';

    public string $roundFilter = '';

    public string $sortBy = 'time';

    public string $sortDirection = 'desc';

    #[Computed]
    public function canManageMatches(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasAnyRole([RoleName::Admin->value, RoleName::Player->value]);
    }

    #[Computed]
    public function canDeleteMatches(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole(RoleName::Admin->value);
    }

    #[Computed]
    public function matches(): LengthAwarePaginator
    {
        $query = Game::query()->with(['group', 'round', 'playerOne', 'playerTwo', 'sets' => fn($query) => $query->orderBy('created_at')]);

        if ($this->playerFilter !== '') {
            $playerId = (int) $this->playerFilter;

            $query->where(function (Builder $builder) use ($playerId): void {
                $builder->where('player_one_id', $playerId)->orWhere('player_two_id', $playerId);
            });
        }

        if ($this->roundFilter !== '') {
            $query->where('round_id', (int) $this->roundFilter);
        }

        $this->applySorting($query);

        return $query->paginate(50);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function playerOptions(): array
    {
        $playerIds = Game::query()
            ->pluck('player_one_id')
            ->merge(Game::query()->pluck('player_two_id'))
            ->filter()
            ->unique()
            ->values();

        return User::query()->whereIn('id', $playerIds)->orderBy('first_name')->orderBy('last_name')->get()->mapWithKeys(fn(User $user): array => [$user->id => $user->full_name])->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function roundOptions(): array
    {
        $roundIds = Game::query()->pluck('round_id')->filter()->unique()->values();

        return Round::query()->whereIn('id', $roundIds)->orderBy('number')->orderBy('id')->get()->mapWithKeys(fn(Round $round): array => [$round->id => $round->name])->all();
    }

    public function updatedPlayerFilter(): void
    {
        $this->resetPage();
    }

    public function updatedRoundFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedSortDirection(): void
    {
        $this->resetPage();
    }

    public function sortByColumn(string $column): void
    {
        if (!in_array($column, ['time', 'status', 'duration'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    public function confirmDelete(int $gameId): void
    {
        if (!$this->canManageMatches) {
            return;
        }

        $this->confirmingDeletionId = $gameId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeletionId = null;
    }

    public function deleteMatch(int $gameId): void
    {
        if (!$this->canDeleteMatches) {
            abort(403);
        }

        if ($this->confirmingDeletionId !== $gameId) {
            return;
        }

        Game::query()->findOrFail($gameId)->delete();
        $this->confirmingDeletionId = null;

        session()->flash('status', 'Meč je uspješno obrisan.');
    }

    public function statusLabel(Game $game): string
    {
        return match ($this->statusType($game)) {
            'waiting' => 'NA ČEKANJU',
            'live' => 'UŽIVO',
            default => 'ZAVRŠENO',
        };
    }

    public function statusBadgeClass(Game $game): string
    {
        return match ($this->statusType($game)) {
            'waiting' => 'border-amber-400/40 bg-amber-400/10 text-amber-700 dark:text-amber-300',
            'live' => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-700 dark:text-emerald-300',
            default => 'border-sky-400/40 bg-sky-400/10 text-sky-700 dark:text-sky-300',
        };
    }

    public function scoreSummary(Game $game): string
    {
        $scores = $game->sets->filter(fn($set): bool => filled($set->player_one_score) && filled($set->player_two_score))->map(fn($set): string => "{$set->player_one_score}-{$set->player_two_score}")->implode(', ');

        return $scores !== '' ? $scores : '—';
    }

    public function setResultSummary(Game $game): string
    {
        $result = Game::determineMatchResultFromSetScores(
            $game->sets
                ->map(
                    fn($set): array => [
                        'player_one_score' => $set->player_one_score,
                        'player_two_score' => $set->player_two_score,
                    ],
                )
                ->all(),
            $game->best_of,
            $game->player_one_id,
            $game->player_two_id,
        );

        return "{$result['player_one_wins']}-{$result['player_two_wins']}";
    }

    public function scoreRoute(Game $game): string
    {
        return route('matches.score', ['game' => $game->id]);
    }

    public function playerClass(Game $game, ?int $playerId): string
    {
        if (!$playerId) {
            return 'text-foreground';
        }

        $result = Game::determineMatchResultFromSetScores(
            $game->sets
                ->map(
                    fn($set): array => [
                        'player_one_score' => $set->player_one_score,
                        'player_two_score' => $set->player_two_score,
                    ],
                )
                ->all(),
            $game->best_of,
            $game->player_one_id,
            $game->player_two_id,
        );

        if (!$result['is_complete']) {
            return 'text-foreground';
        }

        if ($result['is_draw']) {
            return 'text-amber-600/90 dark:text-amber-400/90';
        }

        if (($result['winner_id'] ?? null) === $playerId) {
            return 'text-emerald-600 dark:text-emerald-400';
        }

        return 'text-foreground/70';
    }

    private function statusType(Game $game): string
    {
        if (!$game->started_at && !$game->finished_at) {
            return 'waiting';
        }

        if ($game->started_at && !$game->finished_at) {
            return 'live';
        }

        return 'finished';
    }

    private function applySorting(Builder $query): void
    {
        if ($this->sortBy === 'status') {
            $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

            $query->orderByRaw("case when started_at is null and finished_at is null then 1 when started_at is not null and finished_at is null then 2 else 3 end {$direction}")->orderByDesc('id');

            return;
        }

        if ($this->sortBy === 'duration') {
            if ($this->sortDirection === 'asc') {
                $query->orderByRaw('duration_seconds is null')->orderBy('duration_seconds')->orderByDesc('id');

                return;
            }

            $query->orderByRaw('duration_seconds is null')->orderByDesc('duration_seconds')->orderByDesc('id');

            return;
        }

        if ($this->sortDirection === 'asc') {
            $query->orderBy('created_at')->orderBy('id');

            return;
        }

        $query->orderByDesc('created_at')->orderByDesc('id');
    }
};
?>

<div class="bg-card/80 shadow-sm p-6 border border-border rounded-3xl">
    @if (session('status'))
        <div
            class="bg-emerald-400/10 mb-4 px-4 py-3 border border-emerald-400/40 rounded-2xl text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="sm:hidden mb-4" x-data="{ open: false }">
        <button type="button" @click="open = !open"
            class="inline-flex items-center gap-2 bg-background/70 hover:bg-muted/60 px-3 py-2 border border-border/70 rounded-xl text-foreground text-sm transition">
            <x-heroicon-o-funnel class="w-4 h-4" />
            Filtri
        </button>

        <div x-cloak x-show="open" x-transition @click.outside="open = false"
            class="bg-background/95 shadow-lg mt-3 p-3 border border-border/70 rounded-2xl">
            <div class="gap-3 grid">
                <label
                    class="flex flex-col gap-1 font-semibold text-muted-foreground text-xs uppercase tracking-[0.12em]">
                    Igrač
                    <select wire:model.live="playerFilter"
                        class="bg-background/70 px-3 py-2 border border-border/70 focus:border-foreground/40 rounded-xl focus:outline-none text-foreground text-sm normal-case tracking-normal">
                        <option value="">Svi igrači</option>
                        @foreach ($this->playerOptions as $playerOptionId => $playerName)
                            <option value="{{ $playerOptionId }}">{{ $playerName }}</option>
                        @endforeach
                    </select>
                </label>

                <label
                    class="flex flex-col gap-1 font-semibold text-muted-foreground text-xs uppercase tracking-[0.12em]">
                    Runda
                    <select wire:model.live="roundFilter"
                        class="bg-background/70 px-3 py-2 border border-border/70 focus:border-foreground/40 rounded-xl focus:outline-none text-foreground text-sm normal-case tracking-normal">
                        <option value="">Sve runde</option>
                        @foreach ($this->roundOptions as $roundOptionId => $roundName)
                            <option value="{{ $roundOptionId }}">{{ $roundName }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </div>

    <div class="hidden gap-3 sm:grid sm:grid-cols-2 mb-4">
        <label class="flex flex-col gap-1 font-semibold text-muted-foreground text-xs uppercase tracking-[0.12em]">
            Igrač
            <select wire:model.live="playerFilter"
                class="bg-background/70 px-3 py-2 border border-border/70 focus:border-foreground/40 rounded-xl focus:outline-none text-foreground text-sm normal-case tracking-normal">
                <option value="">Svi igrači</option>
                @foreach ($this->playerOptions as $playerOptionId => $playerName)
                    <option value="{{ $playerOptionId }}">{{ $playerName }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1 font-semibold text-muted-foreground text-xs uppercase tracking-[0.12em]">
            Runda
            <select wire:model.live="roundFilter"
                class="bg-background/70 px-3 py-2 border border-border/70 focus:border-foreground/40 rounded-xl focus:outline-none text-foreground text-sm normal-case tracking-normal">
                <option value="">Sve runde</option>
                @foreach ($this->roundOptions as $roundOptionId => $roundName)
                    <option value="{{ $roundOptionId }}">{{ $roundName }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-4xl text-sm text-left">
            <thead class="text-muted-foreground text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-3">Setovi</th>
                    <th class="px-3 py-3">
                        <button type="button" wire:click="sortByColumn('time')"
                            class="inline-flex items-center gap-1 hover:text-foreground transition">
                            Vrijeme
                            @if ($sortBy === 'time')
                                @if ($sortDirection === 'asc')
                                    <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                @else
                                    <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                @endif
                            @endif
                        </button>
                    </th>
                    <th class="px-3 py-3">
                        <button type="button" wire:click="sortByColumn('duration')"
                            class="inline-flex items-center gap-1 hover:text-foreground transition">
                            Trajanje
                            @if ($sortBy === 'duration')
                                @if ($sortDirection === 'asc')
                                    <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                @else
                                    <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                @endif
                            @endif
                        </button>
                    </th>
                    <th class="px-3 py-3">Grupa</th>
                    <th class="px-3 py-3">
                        <button type="button" wire:click="sortByColumn('status')"
                            class="inline-flex items-center gap-1 hover:text-foreground transition">
                            Status
                            @if ($sortBy === 'status')
                                @if ($sortDirection === 'asc')
                                    <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                @else
                                    <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                @endif
                            @endif
                        </button>
                    </th>
                    @if ($this->canManageMatches)
                        <th class="px-3 py-3 text-right">Akcije</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-border/70">
                @forelse ($this->matches as $game)
                    <tr wire:key="matches-list-game-{{ $game->id }}">
                        <td class="px-3 py-3">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg transition">
                                <p class="font-semibold text-sm">
                                    <span class="{{ $this->playerClass($game, $game->player_one_id) }}">
                                        {{ $game->playerOne?->full_name ?? '—' }}
                                    </span>
                                    <span class="text-muted-foreground">vs</span>
                                    <span class="{{ $this->playerClass($game, $game->player_two_id) }}">
                                        {{ $game->playerTwo?->full_name ?? '—' }}
                                    </span>
                                </p>
                                <p class="mt-0.5 font-semibold text-foreground text-sm">
                                    {{ $this->setResultSummary($game) }}
                                </p>
                                <p class="text-muted-foreground text-xs">{{ $this->scoreSummary($game) }}</p>
                            </a>
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg hover:text-foreground transition">
                                {{ $game->created_at ? $game->created_at->locale('hr')->dayName . ' ' . $game->created_at->format('H:i') : '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg hover:text-foreground transition">
                                {{ $game->duration_seconds ? sprintf('%d:%02d', intdiv($game->duration_seconds, 60), $game->duration_seconds % 60) : '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-3 font-medium">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg transition">
                                {{ $game->group?->name ?? '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg transition">
                                <span
                                    class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] {{ $this->statusBadgeClass($game) }}">
                                    {{ $this->statusLabel($game) }}
                                </span>
                            </a>
                        </td>
                        @if ($this->canManageMatches)
                            <td class="px-3 py-3">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('matches.score', ['game' => $game->id]) }}"
                                        class="px-3 py-1.5 border border-border hover:border-foreground/40 rounded-full font-semibold text-foreground text-xs uppercase tracking-wide transition">
                                        Uredi
                                    </a>

                                    @if ($this->canDeleteMatches)
                                        @if ($confirmingDeletionId === $game->id)
                                            <button type="button" wire:click="deleteMatch({{ $game->id }})"
                                                class="bg-red-500/10 hover:bg-red-500/20 px-3 py-1.5 border border-red-500/40 rounded-full font-semibold text-red-600 dark:text-red-300 text-xs uppercase tracking-wide transition">
                                                Potvrdi brisanje
                                            </button>
                                            <button type="button" wire:click="cancelDelete"
                                                class="px-3 py-1.5 border border-border hover:border-foreground/40 rounded-full font-semibold text-muted-foreground hover:text-foreground text-xs uppercase tracking-wide transition">
                                                Odustani
                                            </button>
                                        @else
                                            <button type="button" wire:click="confirmDelete({{ $game->id }})"
                                                class="hover:bg-red-500/10 px-3 py-1.5 border border-red-500/40 rounded-full font-semibold text-red-600 dark:text-red-300 text-xs uppercase tracking-wide transition">
                                                Obriši
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-8 text-muted-foreground text-sm text-center"
                            colspan="{{ $this->canManageMatches ? 6 : 5 }}">Nema mečeva za
                            prikaz.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $this->matches->links() }}
    </div>
</div>
