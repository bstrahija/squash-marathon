<?php

use App\Livewire\Concerns\HasGameDisplayHelpers;
use App\Models\Event;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use HasGameDisplayHelpers;

    #[Computed]
    public function games(): array
    {
        $event = Event::current();

        if (! $event) {
            return [];
        }

        return $event->latestCompletedGames()
            ->map(function (Game $game): array {
                $result = $game->resultFromSets();

                $isDraw   = $result['is_draw'];
                $winnerId = $result['winner_id'];

                return [
                    'id'               => $game->id,
                    'time'             => $game->created_at,
                    'player_one'       => $game->playerOne->short_name,
                    'player_two'       => $game->playerTwo->short_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
                    'score'            => $game->scoreSummary(),
                    'duration'         => $this->formatDuration($game->duration_seconds),
                ];
            })
            ->all();
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
