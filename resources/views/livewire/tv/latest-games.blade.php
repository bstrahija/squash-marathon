<?php

use App\Models\Event;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
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
                $scores = $game->sets
                    ->filter(fn($set): bool => filled($set->player_one_score) && filled($set->player_two_score))
                    ->map(fn($set): string => "{$set->player_one_score}-{$set->player_two_score}")
                    ->implode(', ');

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

@php
    $typography = match ($this->density) {
        'comfortable' => [
            'title' => 'text-[clamp(1.15rem,1.7vw,2rem)]',
            'meta' => 'text-[clamp(0.8rem,1vw,1.05rem)]',
            'text' => 'text-[clamp(0.95rem,1.3vw,1.3rem)]',
            'subtext' => 'text-[clamp(0.72rem,0.95vw,0.92rem)]',
            'card' => 'px-4 py-3',
        ],
        'balanced' => [
            'title' => 'text-[clamp(1rem,1.35vw,1.55rem)]',
            'meta' => 'text-[clamp(0.7rem,0.85vw,0.9rem)]',
            'text' => 'text-[clamp(0.82rem,1.06vw,1.06rem)]',
            'subtext' => 'text-[clamp(0.64rem,0.74vw,0.78rem)]',
            'card' => 'px-3.5 py-2.5',
        ],
        default => [
            'title' => 'text-[clamp(0.92rem,1.08vw,1.22rem)]',
            'meta' => 'text-[clamp(0.62rem,0.72vw,0.78rem)]',
            'text' => 'text-[clamp(0.72rem,0.86vw,0.9rem)]',
            'subtext' => 'text-[clamp(0.56rem,0.64vw,0.68rem)]',
            'card' => 'px-3 py-2',
        ],
    };
@endphp

<div class="flex h-full min-h-0 flex-col p-[clamp(0.75rem,1vw,1.1rem)]" wire:poll.3s>
    <div class="mb-2.5 flex items-end justify-between gap-2">
        <h2 class="font-display font-semibold text-foreground {{ $typography['title'] }}">Zadnje partije</h2>
        <span class="text-muted-foreground {{ $typography['meta'] }}">30</span>
    </div>

    <div class="min-h-0 flex-1 overflow-hidden bg-background/40">
        <div class="flex h-full min-h-0 flex-col divide-y divide-border/60 overflow-hidden">
            @forelse ($this->games as $game)
                <article class="odd:bg-background/35 even:bg-transparent {{ $typography['card'] }}" wire:key="tv-latest-game-{{ $game['id'] }}">
                    <div class="flex items-center justify-between {{ $typography['subtext'] }} font-semibold text-muted-foreground">
                        <span>{{ $game['time']?->format('H:i') ?? '—' }}</span>
                        <span>Trajanje {{ $game['duration'] }}</span>
                    </div>

                    <p class="mt-1.5 leading-tight font-semibold {{ $typography['text'] }}">
                        <span class="{{ $game['player_one_class'] }}">{{ $game['player_one'] }}</span>
                        <span class="text-muted-foreground">vs</span>
                        <span class="{{ $game['player_two_class'] }}">{{ $game['player_two'] }}</span>
                    </p>

                    <p class="mt-0.5 {{ $typography['subtext'] }} text-muted-foreground">Rezultat {{ $game['score'] }}</p>
                </article>
            @empty
                <div class="px-4 py-6 text-sm text-muted-foreground">
                    Još nema završenih partija.
                </div>
            @endforelse
        </div>
    </div>
</div>