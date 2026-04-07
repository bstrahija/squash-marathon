<?php

use App\Enums\RoleName;
use App\Models\Game;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $confirmingDeletionId = null;

    #[Computed]
    public function canManageMatches(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole(RoleName::Admin->value);
    }

    #[Computed]
    public function matches(): LengthAwarePaginator
    {
        return Game::query()
            ->with(['group', 'round', 'playerOne', 'playerTwo', 'sets' => fn($query) => $query->orderBy('created_at')])
            ->latest('id')
            ->paginate(50);
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
        if (!$this->canManageMatches) {
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
};
?>

<div class="rounded-3xl border border-border bg-card/80 p-6 shadow-sm">
    @if (session('status'))
        <div
            class="mb-4 rounded-2xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-4xl text-left text-sm">
            <thead class="text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-3 py-3">Vrijeme</th>
                    <th class="px-3 py-3">Grupa</th>
                    <th class="px-3 py-3">Igrači</th>
                    <th class="px-3 py-3">Status</th>
                    <th class="px-3 py-3">Setovi</th>
                    <th class="px-3 py-3">Trajanje</th>
                    @if ($this->canManageMatches)
                        <th class="px-3 py-3 text-right">Akcije</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-border/70">
                @forelse ($this->matches as $game)
                    <tr wire:key="matches-list-game-{{ $game->id }}">
                        <td class="px-3 py-3 text-muted-foreground">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block -mx-3 -my-3 rounded-lg px-3 py-3 transition hover:bg-muted/40 hover:text-foreground">
                                {{ $game->created_at ? $game->created_at->locale('hr')->dayName . ' ' . $game->created_at->format('H:i') : '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-3 font-medium">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block -mx-3 -my-3 rounded-lg px-3 py-3 transition hover:bg-muted/40">
                                {{ $game->group?->name ?? '—' }}
                            </a>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block -mx-3 -my-3 rounded-lg px-3 py-3 transition hover:bg-muted/40">
                                <span class="{{ $this->playerClass($game, $game->player_one_id) }}">
                                    {{ $game->playerOne?->full_name ?? '—' }}
                                </span>
                                <span class="text-muted-foreground">vs</span>
                                <span class="{{ $this->playerClass($game, $game->player_two_id) }}">
                                    {{ $game->playerTwo?->full_name ?? '—' }}
                                </span>
                            </a>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block -mx-3 -my-3 rounded-lg px-3 py-3 transition hover:bg-muted/40">
                                <span
                                    class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] {{ $this->statusBadgeClass($game) }}">
                                    {{ $this->statusLabel($game) }}
                                </span>
                            </a>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block -mx-3 -my-3 rounded-lg px-3 py-3 transition hover:bg-muted/40">
                                <p class="text-sm font-semibold text-foreground">{{ $this->setResultSummary($game) }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ $this->scoreSummary($game) }}</p>
                            </a>
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block -mx-3 -my-3 rounded-lg px-3 py-3 transition hover:bg-muted/40 hover:text-foreground">
                                {{ $game->duration_seconds ? sprintf('%d:%02d', intdiv($game->duration_seconds, 60), $game->duration_seconds % 60) : '—' }}
                            </a>
                        </td>
                        @if ($this->canManageMatches)
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('filament.admin.resources.games.edit', ['record' => $game->id]) }}"
                                        class="rounded-full border border-border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-foreground transition hover:border-foreground/40">
                                        Uredi
                                    </a>

                                    @if ($confirmingDeletionId === $game->id)
                                        <button type="button" wire:click="deleteMatch({{ $game->id }})"
                                            class="rounded-full border border-red-500/40 bg-red-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-500/20 dark:text-red-300">
                                            Potvrdi brisanje
                                        </button>
                                        <button type="button" wire:click="cancelDelete"
                                            class="rounded-full border border-border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground transition hover:border-foreground/40 hover:text-foreground">
                                            Odustani
                                        </button>
                                    @else
                                        <button type="button" wire:click="confirmDelete({{ $game->id }})"
                                            class="rounded-full border border-red-500/40 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-500/10 dark:text-red-300">
                                            Obriši
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td class="px-3 py-8 text-center text-sm text-muted-foreground" colspan="7">Nema mečeva za
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
