<?php

use App\Livewire\Concerns\HasGameDisplayHelpers;
use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use HasGameDisplayHelpers;

    #[Computed]
    public function timeline(): array
    {
        $event = Event::current();

        if (! $event) {
            return [];
        }

        $playerIds = $event->resolveParticipants()->pluck('id')->all();

        $games = Game::query()
            ->with(['sets', 'playerOne', 'playerTwo'])
            ->where('event_id', $event->id)
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->get();

        return $games
            ->map(function (Game $game) use ($playerIds): ?array {
                if (! in_array($game->player_one_id, $playerIds, true)) {
                    return null;
                }

                if (! in_array($game->player_two_id, $playerIds, true)) {
                    return null;
                }

                $result = $game->resultFromSets();

                if (! $result['is_complete']) {
                    return null;
                }

                $durationSeconds = $game->duration_seconds;

                if ($durationSeconds === null && $game->started_at && $game->finished_at) {
                    $durationSeconds = $game->started_at->diffInSeconds($game->finished_at);
                }

                $isDraw   = $result['is_draw'];
                $winnerId = $result['winner_id'];

                return [
                    'id'                    => $game->id,
                    'time'                  => $game->finished_at,
                    'player_one'            => $game->playerOne->full_name,
                    'player_two'            => $game->playerTwo->full_name,
                    'player_one_class'      => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_class'      => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
                    'player_one_sets_class' => $this->setScoreClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_sets_class' => $this->setScoreClass($game->player_two_id, $winnerId, $isDraw),
                    'player_one_sets'       => $result['player_one_wins'],
                    'player_two_sets'       => $result['player_two_wins'],
                    'score_details'         => $game->scoreSummary(),
                    'duration'              => $this->formatDuration($durationSeconds),
                ];
            })
            ->filter()
            ->take(24)
            ->values()
            ->all();
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
