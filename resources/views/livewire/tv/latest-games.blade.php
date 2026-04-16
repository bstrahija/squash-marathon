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
            ->limit(90)
            ->get()
            ->map(function (Game $game): array {
                $result = Game::determineMatchResultFromSetCollection($game->sets, (int) $game->best_of, $game->player_one_id, $game->player_two_id);

                $durationSeconds = $game->duration_seconds;

                if ($durationSeconds === null && $game->started_at && $game->finished_at) {
                    $durationSeconds = $game->started_at->diffInSeconds($game->finished_at);
                }

                return [
                    'id' => $game->id,
                    'time' => $game->created_at,
                    'player_one' => $game->playerOne->full_name,
                    'player_two' => $game->playerTwo->full_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $result['winner_id'], (bool) $result['is_draw']),
                    'player_two_class' => $this->playerClass($game->player_two_id, $result['winner_id'], (bool) $result['is_draw']),
                    'score' => Game::formatSetPointsSummary($game->sets),
                    'duration' => $this->formatDuration($durationSeconds),
                    'is_complete' => (bool) $result['is_complete'],
                ];
            })
            ->filter(fn(array $game): bool => $game['is_complete'])
            ->take(30)
            ->values()
            ->all();
    }

    #[Computed]
    public function density(): string
    {
        $rows = count($this->games);

        if ($rows <= 10) {
            return 'comfortable';
        }

        if ($rows <= 18) {
            return 'balanced';
        }

        return 'compact';
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

<div class="tv-latest-games tv-density-{{ $this->density }} flex h-full min-h-0 flex-col" wire:poll.3s>
    <div class="bg-background/40 min-h-0 flex-1 overflow-hidden">
        <div class="divide-border/60 flex h-full min-h-0 flex-col divide-y overflow-hidden">
            @forelse ($this->games as $game)
                <article class="tv-latest-game-card odd:bg-background/35 even:bg-transparent"
                         wire:key="tv-latest-game-{{ $game['id'] }}">
                    <div class="tv-latest-subtext text-muted-foreground flex items-center justify-between font-semibold">
                        <span>{{ $game['time']?->format('H:i') ?? '—' }}</span>
                        <span>Trajanje {{ $game['duration'] }}</span>
                    </div>

                    <p class="tv-latest-text mt-1.5 font-semibold leading-tight">
                        <span class="{{ $game['player_one_class'] }}">{{ $game['player_one'] }}</span>
                        <span class="text-muted-foreground">vs</span>
                        <span class="{{ $game['player_two_class'] }}">{{ $game['player_two'] }}</span>
                    </p>

                    <p class="tv-latest-subtext text-muted-foreground mt-0.5">Rezultat {{ $game['score'] }}
                    </p>
                </article>
            @empty
                <div class="text-muted-foreground px-4 py-6 text-sm">
                    Još nema završenih partija.
                </div>
            @endforelse
        </div>
    </div>
</div>
