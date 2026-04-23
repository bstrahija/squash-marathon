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

        if (!$event) {
            return [];
        }

        return $event
            ->latestCompletedGames(30)
            ->map(function (Game $game): array {
                $result = $game->resultFromSets();
                $isDraw = $result['is_draw'];
                $winnerId = $result['winner_id'];

                return [
                    'id' => $game->id,
                    'time' => $game->created_at,
                    'player_one' => $game->playerOne->short_name,
                    'player_two' => $game->playerTwo->short_name,
                    'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
                    'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
                    'score' => $game->scoreSummary(),
                    'is_live' => $game->isLive(),
                    'started_at_ts' => $game->started_at?->timestamp,
                    'duration' => $this->matchDurationLabel($game, $game->isLive()),
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
                        <span>{{ $game['time']?->setTimezone(config('app.display_timezone'))->format('H:i') ?? '—' }}</span>
                        <span class="flex items-center gap-1" x-data="{
                            startedAtTs: {{ $game['started_at_ts'] ?? 'null' }},
                            isLive: {{ $game['is_live'] ? 'true' : 'false' }},
                            now: Math.floor(Date.now() / 1000),
                            get elapsed() {
                                if (!this.isLive || this.startedAtTs === null) return null;
                                return Math.max(0, this.now - this.startedAtTs);
                            },
                            formatDuration(totalSeconds) {
                                if (totalSeconds === null || totalSeconds <= 0) return '—';
                                const hours = Math.floor(totalSeconds / 3600);
                                const minutes = Math.floor((totalSeconds % 3600) / 60);
                                const secs = totalSeconds % 60;
                                if (hours > 0) return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                                return `${minutes}:${String(secs).padStart(2, '0')}`;
                            },
                        }" x-init="if (isLive && startedAtTs !== null) {
                            const interval = setInterval(() => { now = Math.floor(Date.now() / 1000); }, 1000);
                            $cleanup(() => clearInterval(interval));
                        }">
                            <x-heroicon-o-clock class="size-3 shrink-0" />
                            <span x-show="!isLive" x-cloak>{{ $game['duration'] }}</span>
                            <span x-show="isLive" x-cloak x-text="formatDuration(elapsed)"></span>
                        </span>
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
