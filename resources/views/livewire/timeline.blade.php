<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function timeline(): array
    {
        $event = Event::query()->latest('start_at')->first();

        if (!$event) {
            return [];
        }

        $players = $event->users()->get();

        if ($players->isEmpty()) {
            $players = User::role(RoleName::Player->value)->get();
        }

        if ($players->isEmpty()) {
            $players = User::query()->get();
        }

        $playerIds = $players->pluck('id')->all();

        $games = Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $event->id)
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->get();

        return $games
            ->filter(function ($game) use ($playerIds): bool {
                if (!in_array($game->player_one_id, $playerIds, true)) {
                    return false;
                }

                if (!in_array($game->player_two_id, $playerIds, true)) {
                    return false;
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

                return $result['is_complete'];
            })
            ->map(function ($game): array {
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
                $playerOneClass = $this->playerClass($game->player_one_id, $winnerId, $isDraw);
                $playerTwoClass = $this->playerClass($game->player_two_id, $winnerId, $isDraw);
                $playerOneSetsClass = $this->setScoreClass($game->player_one_id, $winnerId, $isDraw);
                $playerTwoSetsClass = $this->setScoreClass($game->player_two_id, $winnerId, $isDraw);
                return [
                    'id' => $game->id,
                    'time' => $game->finished_at,
                    'player_one' => $game->playerOne->full_name,
                    'player_two' => $game->playerTwo->full_name,
                    'player_one_class' => $playerOneClass,
                    'player_two_class' => $playerTwoClass,
                    'player_one_sets_class' => $playerOneSetsClass,
                    'player_two_sets_class' => $playerTwoSetsClass,
                    'player_one_sets' => $result['player_one_wins'],
                    'player_two_sets' => $result['player_two_wins'],
                    'score_details' => $scores !== '' ? $scores : '—',
                    'duration' => $this->formatDuration($durationSeconds),
                ];
            })
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

<div class="bg-card shadow-sm p-6 border border-border rounded-3xl">
    <div class="flex flex-wrap justify-between items-end gap-4">
        <div>
            <p class="font-semibold text-muted-foreground text-xs uppercase tracking-[0.2em]">Kronologija</p>
            <h2 class="mt-2 font-display font-semibold text-2xl">Najsvježije završene partije</h2>
        </div>
        <p class="text-muted-foreground text-xs">Zadnjih 24 završenih partija.</p>
    </div>
    <div class="gap-3 grid sm:grid-cols-2 xl:grid-cols-3 mt-5">
        @forelse ($this->timeline as $entry)
            <div class="bg-background/70 p-4 border border-border/70 rounded-2xl" wire:key="timeline-{{ $entry['id'] }}">
                <div class="items-end gap-x-3 gap-y-1 grid grid-cols-[1fr_auto_1fr]">
                    <span class="{{ $entry['player_one_class'] }} truncate text-center text-sm font-semibold">
                        {{ $entry['player_one'] }}
                    </span>
                    <span class="font-semibold text-[10px] text-muted-foreground uppercase tracking-[0.2em]">vs</span>
                    <span class="{{ $entry['player_two_class'] }} truncate text-center text-sm font-semibold">
                        {{ $entry['player_two'] }}
                    </span>

                    <p
                        class="font-display text-center text-4xl leading-none font-semibold {{ $entry['player_one_sets_class'] }}">
                        {{ $entry['player_one_sets'] }}
                    </p>
                    <p class="font-display text-muted-foreground text-xl leading-none"></p>
                    <p
                        class="font-display text-center text-4xl leading-none font-semibold {{ $entry['player_two_sets_class'] }}">
                        {{ $entry['player_two_sets'] }}
                    </p>
                </div>

                <p class="mt-1 font-medium text-foreground/90 text-sm text-center">
                    {{ $entry['score_details'] }}
                </p>

                {{-- <p class="mt-3 text-muted-foreground text-xs text-center">
                    Trajanje {{ $entry['duration'] }} • {{ $entry['time']?->format('H:i') ?? '—' }}
                </p> --}}
                <p class="mt-3 text-muted-foreground text-xs text-center">
                    Trajanje {{ $entry['duration'] }}
                </p>
            </div>
        @empty
            <div
                class="bg-background/70 px-4 py-6 border border-border/70 border-dashed rounded-2xl text-muted-foreground text-sm">
                Još nema završenih partija.
            </div>
        @endforelse
    </div>
</div>
