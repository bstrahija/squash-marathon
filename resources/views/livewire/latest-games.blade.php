<?php

use App\Models\Event;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function games(): array
    {
        $event = Event::query()->latest('start_at')->first();

        if (!$event) {
            return [];
        }

        return Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $event->id)
            ->get()
            ->filter(function (Game $game): bool {
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

                return $result['is_complete'];
            })
            ->map(function (Game $game): array {
                $scores = $game->sets->filter(fn($set): bool => filled($set->player_one_score) && filled($set->player_two_score))->map(fn($set): string => "{$set->player_one_score}-{$set->player_two_score}")->implode(', ');
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
                $isDraw = (bool) ($result['is_complete'] && $result['is_draw']);
                $winnerId = $result['winner_id'] ?? null;
                $playerOneClass = $this->playerClass($game->player_one_id, $winnerId, $isDraw);
                $playerTwoClass = $this->playerClass($game->player_two_id, $winnerId, $isDraw);

                return [
                    'id' => $game->id,
                    'time' => $game->created_at,
                    'player_one' => $game->playerOne->full_name,
                    'player_two' => $game->playerTwo->full_name,
                    'player_one_class' => $playerOneClass,
                    'player_two_class' => $playerTwoClass,
                    'score' => $scores !== '' ? $scores : '—',
                    'duration' => $this->formatDuration($game->duration_seconds),
                ];
            })
            ->sortByDesc('time')
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

<div class="rounded-3xl border border-border bg-card p-5 shadow-sm" wire:poll.5s>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Najnovije</p>
            <h2 class="font-display mt-1 text-xl font-semibold">Zadnjih 20 partija</h2>
        </div>
        <p class="text-[11px] text-muted-foreground">Skrolaj za više</p>
    </div>

    <div class="mt-3 max-h-[60vh] overflow-y-auto pr-2">
        <div class="grid gap-2 sm:grid-cols-2">
            @forelse ($this->games as $game)
                <div class="rounded-2xl border border-border/70 bg-background/70 p-3"
                    wire:key="latest-game-{{ $game['id'] }}">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-muted-foreground">
                        <span>{{ $game['time']?->format('H:i') ?? '—' }}</span>
                        <span
                            class="rounded-full border border-border/70 bg-card px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.18em] text-foreground">
                            Kraj
                        </span>
                    </div>
                    <p class="mt-2 text-sm font-semibold">
                        <span class="{{ $game['player_one_class'] }}">{{ $game['player_one'] }}</span>
                        <span class="text-muted-foreground">vs</span>
                        <span class="{{ $game['player_two_class'] }}">{{ $game['player_two'] }}</span>
                    </p>
                    <p class="mt-0.5 text-[11px] text-muted-foreground">Rezultat</p>
                    <p class="mt-0.5 text-sm font-semibold text-foreground">{{ $game['score'] }}</p>
                    <p class="mt-0.5 text-[11px] text-muted-foreground">Trajanje {{ $game['duration'] }}</p>
                </div>
            @empty
                <div
                    class="rounded-2xl border border-dashed border-border/70 bg-background/70 px-4 py-6 text-sm text-muted-foreground">
                    Još nema završenih partija.
                </div>
            @endforelse
        </div>
    </div>
</div>
