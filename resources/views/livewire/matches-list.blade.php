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
};
?>

<div class="bg-card/80 shadow-sm p-6 border border-border rounded-3xl">
    @if (session('status'))
        <div
            class="bg-emerald-400/10 mb-4 px-4 py-3 border border-emerald-400/40 rounded-2xl text-emerald-700 dark:text-emerald-300 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-4xl text-sm text-left">
            <thead class="text-muted-foreground text-xs uppercase tracking-wider">
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
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg hover:text-foreground transition">
                                {{ $game->created_at ? $game->created_at->locale('hr')->dayName . ' ' . $game->created_at->format('H:i') : '—' }}
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
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg transition">
                                <span
                                    class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] {{ $this->statusBadgeClass($game) }}">
                                    {{ $this->statusLabel($game) }}
                                </span>
                            </a>
                        </td>
                        <td class="px-3 py-3">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg transition">
                                <p class="font-semibold text-foreground text-sm">{{ $this->setResultSummary($game) }}
                                </p>
                                <p class="text-muted-foreground text-xs">{{ $this->scoreSummary($game) }}</p>
                            </a>
                        </td>
                        <td class="px-3 py-3 text-muted-foreground">
                            <a href="{{ $this->scoreRoute($game) }}"
                                class="block hover:bg-muted/40 -mx-3 -my-3 px-3 py-3 rounded-lg hover:text-foreground transition">
                                {{ $game->duration_seconds ? sprintf('%d:%02d', intdiv($game->duration_seconds, 60), $game->duration_seconds % 60) : '—' }}
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
                        <td class="px-3 py-8 text-muted-foreground text-sm text-center" colspan="7">Nema mečeva za
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
