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

                $durationSeconds = $game->duration_seconds;

                if ($durationSeconds === null && $game->started_at && $game->finished_at) {
                    $durationSeconds = $game->started_at->diffInSeconds($game->finished_at);
                }

                $isDraw = (bool) ($result['is_complete'] && $result['is_draw']);
                $winnerId = $result['winner_id'] ?? null;

                return [
                    'id' => $game->id,
                    'time' => $game->created_at,
                    'player_one' => $game->playerOne->full_name,
                    'player_two' => $game->playerTwo->full_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
                    'score' => $scores !== '' ? $scores : '—',
                    'duration' => $this->formatDuration($durationSeconds),
                ];
            })
            ->sortByDesc('time')
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
    <div class="min-h-0 flex-1 overflow-hidden bg-background/40">
        <div class="flex h-full min-h-0 flex-col divide-y divide-border/60 overflow-hidden">
            @forelse ($this->games as $game)
                <article class="tv-latest-game-card odd:bg-background/35 even:bg-transparent"
                    wire:key="tv-latest-game-{{ $game['id'] }}">
                    <div class="tv-latest-subtext flex items-center justify-between font-semibold text-muted-foreground">
                        <span>{{ $game['time']?->format('H:i') ?? '—' }}</span>
                        <span>Trajanje {{ $game['duration'] }}</span>
                    </div>

                    <p class="tv-latest-text mt-1.5 leading-tight font-semibold">
                        <span class="{{ $game['player_one_class'] }}">{{ $game['player_one'] }}</span>
                        <span class="text-muted-foreground">vs</span>
                        <span class="{{ $game['player_two_class'] }}">{{ $game['player_two'] }}</span>
                    </p>

                    <p class="tv-latest-subtext mt-0.5 text-muted-foreground">Rezultat {{ $game['score'] }}
                    </p>
                </article>
            @empty
                <div class="px-4 py-6 text-sm text-muted-foreground">
                    Još nema završenih partija.
                </div>
            @endforelse
        </div>
    </div>
</div>
