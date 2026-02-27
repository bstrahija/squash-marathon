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
            ->get();

        return $games
            ->filter(function ($game) use ($playerIds): bool {
                if (!in_array($game->player_one_id, $playerIds, true)) {
                    return false;
                }

                if (!in_array($game->player_two_id, $playerIds, true)) {
                    return false;
                }

                $winnerId = Game::determineWinnerIdFromSetScores(
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

                return $winnerId !== null;
            })
            ->map(function ($game): array {
                $scores = $game->sets->filter(fn($set): bool => filled($set->player_one_score) && filled($set->player_two_score))->map(fn($set): string => "{$set->player_one_score}-{$set->player_two_score}")->implode(', ');

                return [
                    'id' => $game->id,
                    'time' => $game->created_at,
                    'game' => $game->playerOne->full_name . ' vs ' . $game->playerTwo->full_name,
                    'score' => $scores !== '' ? $scores : '—',
                ];
            })
            ->sortByDesc('time')
            ->take(24)
            ->values()
            ->all();
    }
};
?>

<div class="rounded-3xl border border-border bg-card p-6 shadow-sm" wire:poll.5s>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Kronologija</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Najsvježije završene partije</h2>
        </div>
        <p class="text-xs text-muted-foreground">Zadnjih 24 završenih partija.</p>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->timeline as $entry)
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4" wire:key="timeline-{{ $entry['id'] }}">
                <div class="flex items-center justify-between text-xs font-semibold text-muted-foreground">
                    <span>{{ $entry['time']?->format('H:i') ?? '—' }}</span>
                    <span
                        class="rounded-full border border-border/70 bg-card px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-foreground">
                        Kraj
                    </span>
                </div>
                <p class="mt-3 text-sm font-semibold text-foreground">{{ $entry['game'] }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Rezultat</p>
                <p class="mt-1 text-sm font-semibold text-foreground">{{ $entry['score'] }}</p>
            </div>
        @empty
            <div
                class="rounded-2xl border border-dashed border-border/70 bg-background/70 px-4 py-6 text-sm text-muted-foreground">
                Još nema završenih partija.
            </div>
        @endforelse
    </div>
</div>
