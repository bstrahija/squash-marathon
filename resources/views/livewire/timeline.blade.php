<?php

use App\Models\Event;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function timeline(): array
    {
        $event = Event::current();

        if (!$event) {
            return [];
        }

        $players = $event->resolvedPlayers();
        $playerIds = $players->pluck('id')->all();

        return Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $event->id)
            ->whereIn('player_one_id', $playerIds)
            ->whereIn('player_two_id', $playerIds)
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->limit(80)
            ->get()
            ->map(function (Game $game): array {
                $result = Game::determineMatchResultFromSetCollection($game->sets, (int) $game->best_of, $game->player_one_id, $game->player_two_id);

                $durationSeconds = $game->duration_seconds;

                if ($durationSeconds === null && $game->started_at && $game->finished_at) {
                    $durationSeconds = $game->started_at->diffInSeconds($game->finished_at);
                }

                $isDraw = (bool) $result['is_draw'];
                $winnerId = $result['winner_id'] ?? null;

                return [
                    'id' => $game->id,
                    'time' => $game->finished_at,
                    'player_one' => $game->playerOne->full_name,
                    'player_two' => $game->playerTwo->full_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
                    'player_one_sets_class' => $this->setScoreClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_sets_class' => $this->setScoreClass($game->player_two_id, $winnerId, $isDraw),
                    'player_one_sets' => $result['player_one_wins'],
                    'player_two_sets' => $result['player_two_wins'],
                    'score_details' => Game::formatSetPointsSummary($game->sets),
                    'duration' => $this->formatDuration($durationSeconds),
                    'is_complete' => (bool) $result['is_complete'],
                ];
            })
            ->filter(fn(array $entry): bool => $entry['is_complete'])
            ->take(24)
            ->values()
            ->all();
    }

    private function formatDuration(?int $seconds): string
    {
        if (!$seconds) {
            return '—';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    private function playerClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        if (!$playerId) {
            return 'text-foreground';
        }

        if ($isDraw) {
            return 'text-amber-600/90 dark:text-amber-400/90';
        }

        if ($winnerId && $playerId === $winnerId) {
            return 'text-emerald-600 dark:text-emerald-400';
        }

        return 'text-foreground/70';
    }

    private function setScoreClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        if (!$playerId || $isDraw || !$winnerId) {
            return 'text-foreground';
        }

        if ($playerId === $winnerId) {
            return 'text-foreground';
        }

        return 'text-foreground/60';
    }
};
?>

<div class="bg-card border-border rounded-3xl border p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">Kronologija</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Najsvježije završene partije</h2>
        </div>
        <p class="text-muted-foreground text-xs">Zadnjih 24 završenih partija.</p>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->timeline as $entry)
            <div class="bg-background/70 border-border/70 rounded-2xl border p-4" wire:key="timeline-{{ $entry['id'] }}">
                <div class="grid grid-cols-[1fr_auto_1fr] items-end gap-x-3 gap-y-1">
                    <span class="{{ $entry['player_one_class'] }} truncate text-center text-sm font-semibold">
                        {{ $entry['player_one'] }}
                    </span>
                    <span class="text-muted-foreground text-[10px] font-semibold uppercase tracking-[0.2em]">vs</span>
                    <span class="{{ $entry['player_two_class'] }} truncate text-center text-sm font-semibold">
                        {{ $entry['player_two'] }}
                    </span>

                    <p
                       class="font-display {{ $entry['player_one_sets_class'] }} text-center text-4xl font-semibold leading-none">
                        {{ $entry['player_one_sets'] }}
                    </p>
                    <p class="font-display text-muted-foreground text-xl leading-none"></p>
                    <p
                       class="font-display {{ $entry['player_two_sets_class'] }} text-center text-4xl font-semibold leading-none">
                        {{ $entry['player_two_sets'] }}
                    </p>
                </div>

                <p class="text-foreground/90 mt-1 text-center text-sm font-medium">
                    {{ $entry['score_details'] }}
                </p>

                <p class="text-muted-foreground mt-3 text-center text-xs">
                    Trajanje {{ $entry['duration'] }}
                </p>
            </div>
        @empty
            <div
                 class="bg-background/70 border-border/70 text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-sm">
                Još nema završenih partija.
            </div>
        @endforelse
    </div>
</div>
