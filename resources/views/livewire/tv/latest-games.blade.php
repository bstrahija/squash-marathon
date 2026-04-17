<?php

use App\Livewire\Concerns\HasGameDisplayHelpers;
use App\Models\Event;
use App\Models\Game;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use HasGameDisplayHelpers;

    #[Computed]
    public function games(): array
    {
        $event = Event::current();

        if (! $event) {
            return [];
        }

        return $event->latestCompletedGames(30)
            ->map(function (Game $game): array {
                $result   = $game->resultFromSets();
                $isDraw   = $result['is_draw'];
                $winnerId = $result['winner_id'];

                return [
                    'id'               => $game->id,
                    'time'             => $game->created_at,
                    'player_one'       => $game->playerOne->full_name,
                    'player_two'       => $game->playerTwo->full_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
                    'score'            => $game->scoreSummary(),
                    'duration'         => $this->formatDuration($game->duration_seconds),
                ];
            })
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
