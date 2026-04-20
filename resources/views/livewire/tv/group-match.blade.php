<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $groupNumber = 1;

    public function mount(int $groupNumber = 1): void
    {
        $this->groupNumber = $groupNumber;
    }

    #[Computed]
    public function match(): ?array
    {
        $event = Event::current();

        if (!$event) {
            return null;
        }

        $games = Game::query()
            ->with(['sets', 'playerOne', 'playerTwo', 'group', 'gameLogs' => fn($query) => $query->orderBy('sequence')])
            ->where('event_id', $event->id)
            ->whereHas('group', fn($query) => $query->where('number', $this->groupNumber))
            ->latest('id')
            ->limit(20)
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $liveGame = $games->filter(fn(Game $game): bool => $this->isLiveGame($game))->sortByDesc('id')->first();

        if ($liveGame) {
            return $this->mapGame($liveGame);
        }

        $latestGame = $games->sortByDesc('id')->first();

        if (!$latestGame) {
            return null;
        }

        return $this->mapGame($latestGame);
    }

    private function mapGame(Game $game): array
    {
        $orderedSets = $game->sets->sortBy('created_at')->values();
        $latestSet = $orderedSets->last();
        $latestLog = $game->gameLogs->last();

        $result = Game::determineMatchResultFromSetCollection($orderedSets, (int) $game->best_of, $game->player_one_id, $game->player_two_id);

        $isLive = $this->isLiveGame($game);
        $isFinished = $this->isFinishedGame($game);
        $isDraw = (bool) ($result['is_complete'] && $result['is_draw']);
        $winnerId = $result['winner_id'] ?? null;

        return [
            'id' => $game->id,
            'score_url' => route('matches.score', $game),
            'group_name' => $game->group?->name ?? "Group {$this->groupNumber}",
            'player_one' => $game->playerOne?->short_name ?? 'Igrac 1',
            'player_two' => $game->playerTwo?->short_name ?? 'Igrac 2',
            'player_one_full' => $game->playerOne?->full_name ?? 'Igrac 1',
            'player_two_full' => $game->playerTwo?->full_name ?? 'Igrac 2',
            'player_one_current' => (int) ($latestLog?->player_one_score ?? ($latestSet?->player_one_score ?? 0)),
            'player_two_current' => (int) ($latestLog?->player_two_score ?? ($latestSet?->player_two_score ?? 0)),
            'sets_one' => $result['player_one_wins'],
            'sets_two' => $result['player_two_wins'],
            'timeline' => $orderedSets
                ->filter(fn(GameSet $set): bool => filled($set->player_one_score) && filled($set->player_two_score))
                ->map(
                    fn(GameSet $set): array => [
                        'id' => $set->id,
                        'score' => "{$set->player_one_score}:{$set->player_two_score}",
                    ],
                )
                ->all(),
            'status' => $isLive ? 'UŽIVO' : ($isFinished ? 'ZAVRŠENO' : 'NA ČEKANJU'),
            'status_class' => $isLive ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : ($isFinished ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'),
            'status_effect_class' => $isLive ? 'tv-group-status-live' : '',
            'duration_label' => $this->matchDurationLabel($game, $isLive, $isFinished),
            'duration_seconds' => $this->matchDurationSeconds($game, $isLive, $isFinished),
            'duration_is_live' => $game->started_at !== null && $game->finished_at === null,
            'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
            'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
        ];
    }

    private function matchDurationLabel(Game $game, bool $isLive, bool $isFinished): string
    {
        $seconds = $this->matchDurationSeconds($game, $isLive, $isFinished);

        if ($seconds === null) {
            return '—';
        }

        return $this->formatDuration($seconds);
    }

    private function matchDurationSeconds(Game $game, bool $isLive, bool $isFinished): ?int
    {
        if ($game->started_at && !$game->finished_at) {
            return $game->started_at->diffInSeconds(now());
        }

        if ($isFinished && $game->duration_seconds !== null) {
            return (int) $game->duration_seconds;
        }

        if ($isFinished && $game->started_at && $game->finished_at) {
            return $game->started_at->diffInSeconds($game->finished_at);
        }

        return null;
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    private function isLiveGame(Game $game): bool
    {
        if (!$game->started_at || $game->finished_at) {
            return false;
        }

        return !$this->isFinishedGame($game);
    }

    private function isFinishedGame(Game $game): bool
    {
        $result = Game::determineMatchResultFromSetCollection($game->sets, (int) $game->best_of, $game->player_one_id, $game->player_two_id);

        return (bool) ($game->finished_at || $result['is_complete']);
    }

    private function playerClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        if (!$playerId) {
            return 'text-foreground';
        }

        if ($isDraw) {
            return 'text-amber-600/90 dark:text-amber-400/90';
        }

        if ($winnerId && $winnerId === $playerId) {
            return 'text-emerald-600 dark:text-emerald-400';
        }

        if ($winnerId) {
            return 'text-foreground/70';
        }

        return 'text-foreground';
    }
};
?>

@php
    $match = $this->match;
@endphp

<div class="tv-group-match flex h-full min-h-0 flex-col" wire:poll.3s>
    @if ($match)
        <a href="{{ $match['score_url'] }}" aria-label="Open match score"
           class="tv-group-grid bg-background/35 hover:bg-background/50 focus-visible:bg-background/50 grid h-full min-h-0 flex-1 items-stretch overflow-hidden transition-colors focus-visible:outline-none">
            <div
                 class="tv-group-player-column tv-group-player-one flex h-full min-h-0 flex-col items-center justify-center text-center">
                <p title="{{ $match['player_one_full'] }}"
                   class="tv-group-player-name {{ $match['player_one_class'] }} truncate whitespace-nowrap font-semibold leading-tight">
                    {{ $match['player_one'] }}</p>
                <p
                   class="tv-group-point font-display text-foreground/80 self-center text-center font-normal leading-none">
                    {{ $match['sets_one'] }}</p>
            </div>

            <div class="tv-group-center flex h-full min-w-40 flex-col items-center justify-center">
                <p class="tv-group-name text-muted-foreground font-semibold uppercase tracking-wide">
                    {{ $match['group_name'] }}
                </p>
                <div class="text-foreground/80 flex items-center gap-2" x-data="{
                    initialSeconds: {{ $match['duration_seconds'] ?? 'null' }},
                    isLive: {{ $match['duration_is_live'] ? 'true' : 'false' }},
                    currentSeconds: {{ $match['duration_seconds'] ?? 'null' }},
                    timerId: null,
                    toInt(value) {
                        const parsed = Number(value);
                
                        return Number.isFinite(parsed) ? Math.max(0, Math.floor(parsed)) : null;
                    },
                    formatDuration(totalSeconds) {
                        if (totalSeconds === null) {
                            return '—';
                        }
                
                        const safeSeconds = Math.max(0, Math.floor(totalSeconds));
                        const hours = Math.floor(safeSeconds / 3600);
                        const minutes = Math.floor((safeSeconds % 3600) / 60);
                        const seconds = safeSeconds % 60;
                
                        if (hours > 0) {
                            return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                        }
                
                        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    },
                    get currentLabel() {
                        return this.formatDuration(this.currentSeconds);
                    },
                    tick() {
                        if (!this.isLive || this.currentSeconds === null) {
                            return;
                        }
                
                        this.currentSeconds += 1;
                    },
                    init() {
                        this.currentSeconds = this.toInt(this.initialSeconds);
                
                        if (this.isLive && this.currentSeconds !== null) {
                            this.timerId = setInterval(() => this.tick(), 1000);
                        }
                    },
                    destroy() {
                        if (this.timerId) {
                            clearInterval(this.timerId);
                        }
                    },
                }" x-init="init()">
                    <x-heroicon-o-clock class="h-4 w-4" />
                    <span class="text-sm font-semibold tracking-wide" x-text="currentLabel">{{ $match['duration_label'] }}</span>
                </div>
                <span
                      class="tv-group-status {{ $match['status_class'] }} {{ $match['status_effect_class'] }} rounded-full px-3 py-1 font-semibold tracking-wide">
                    {{ $match['status'] }}
                </span>
                <div
                     class="tv-group-set-score font-display text-foreground text-nowrap font-semibold leading-none tracking-tight">
                    {{ $match['player_one_current'] }} : {{ $match['player_two_current'] }}
                </div>

                <div class="tv-group-timeline flex w-full flex-wrap items-center justify-center overflow-hidden">
                    @forelse ($match['timeline'] as $timeline)
                        <span
                              class="tv-group-chip bg-background/80 text-muted-foreground shrink-0 rounded-full font-semibold"
                              wire:key="tv-group-{{ $match['id'] }}-set-{{ $timeline['id'] }}">
                            {{ $timeline['score'] }}
                        </span>
                    @empty
                    @endforelse
                </div>
            </div>

            <div
                 class="tv-group-player-column tv-group-player-two flex h-full min-h-0 flex-col items-center justify-center text-center">
                <p title="{{ $match['player_two_full'] }}"
                   class="tv-group-player-name {{ $match['player_two_class'] }} truncate whitespace-nowrap font-semibold leading-tight">
                    {{ $match['player_two'] }}</p>
                <p
                   class="tv-group-point font-display text-foreground/80 self-center text-center font-normal leading-none">
                    {{ $match['sets_two'] }}</p>
            </div>
        </a>
    @else
        <div
             class="tv-group-empty bg-background/35 text-muted-foreground flex min-h-0 flex-1 items-center justify-center px-6 text-center">
            Nema meceva za ovu grupu.
        </div>
    @endif
</div>
