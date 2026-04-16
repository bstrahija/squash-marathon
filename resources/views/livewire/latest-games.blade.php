<?php

use App\Models\Event;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function games(): array
    {
        $event = Event::current();

        if (!$event) {
            return [];
        }

        return Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $event->id)
            ->latest('created_at')
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(function (Game $game): array {
                $result = Game::determineMatchResultFromSetCollection($game->sets, (int) $game->best_of, $game->player_one_id, $game->player_two_id);

                return [
                    'id' => $game->id,
                    'time' => $game->created_at,
                    'player_one' => $game->playerOne->full_name,
                    'player_two' => $game->playerTwo->full_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $result['winner_id'], (bool) $result['is_draw']),
                    'player_two_class' => $this->playerClass($game->player_two_id, $result['winner_id'], (bool) $result['is_draw']),
                    'score' => Game::formatSetPointsSummary($game->sets),
                    'duration' => $this->formatDuration($game->duration_seconds),
                    'is_complete' => (bool) $result['is_complete'],
                ];
            })
            ->filter(fn(array $game): bool => $game['is_complete'])
            ->take(20)
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
};
?>

<div class="border-border bg-card rounded-3xl border p-5 shadow-sm" wire:poll.5s>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">Najnovije</p>
            <h2 class="font-display mt-1 text-xl font-semibold">Zadnjih 20 partija</h2>
        </div>
        <p class="text-muted-foreground text-[11px]">Skrolaj za više</p>
    </div>

    <div class="mt-3 max-h-[60vh] overflow-y-auto pr-2">
        <div class="grid gap-2 sm:grid-cols-2">
            @forelse ($this->games as $game)
                <div class="border-border/70 bg-background/70 rounded-2xl border p-3"
                     wire:key="latest-game-{{ $game['id'] }}">
                    <div class="text-muted-foreground flex items-center justify-between text-[11px] font-semibold">
                        <span>{{ $game['time']?->format('H:i') ?? '—' }}</span>
                        <span
                              class="border-border/70 bg-card text-foreground rounded-full border px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.18em]">
                            Kraj
                        </span>
                    </div>
                    <p class="mt-2 text-sm font-semibold">
                        <span class="{{ $game['player_one_class'] }}">{{ $game['player_one'] }}</span>
                        <span class="text-muted-foreground">vs</span>
                        <span class="{{ $game['player_two_class'] }}">{{ $game['player_two'] }}</span>
                    </p>
                    <p class="text-muted-foreground mt-0.5 text-[11px]">Rezultat</p>
                    <p class="text-foreground mt-0.5 text-sm font-semibold">{{ $game['score'] }}</p>
                    <p class="text-muted-foreground mt-0.5 text-[11px]">Trajanje {{ $game['duration'] }}</p>
                </div>
            @empty
                <div
                     class="border-border/70 bg-background/70 text-muted-foreground rounded-2xl border border-dashed px-4 py-6 text-sm">
                    Još nema završenih partija.
                </div>
            @endforelse
        </div>
    </div>
</div>
