<?php

use App\Livewire\Concerns\HasGameDisplayHelpers;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use HasGameDisplayHelpers;

    public int $groupNumber = 1;

    public function mount(int $groupNumber = 1): void
    {
        $this->groupNumber = $groupNumber;
    }

    #[Computed]
    public function match(): ?array
    {
        $event = Event::current();

        if (! $event) {
            return null;
        }

        $games = Game::query()
            ->with(['sets', 'playerOne', 'playerTwo', 'group', 'gameLogs' => fn ($query) => $query->orderBy('sequence')])
            ->where('event_id', $event->id)
            ->whereHas('group', fn ($query) => $query->where('number', $this->groupNumber))
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $liveGame = $games->filter(fn (Game $game): bool => $this->isLiveGame($game))->sortByDesc('id')->first();

        if ($liveGame) {
            return $this->mapGame($liveGame);
        }

        $latestGame = $games->sortByDesc('id')->first();

        if (! $latestGame) {
            return null;
        }

        return $this->mapGame($latestGame);
    }

    private function mapGame(Game $game): array
    {
        $orderedSets = $game->sets->sortBy('created_at')->values();
        $latestSet   = $orderedSets->last();
        $latestLog   = $game->gameLogs->last();

        $result = $game->resultFromSets();

        $isLive     = $this->isLiveGame($game);
        $isFinished = $this->isFinishedGame($game);
        $isDraw     = (bool) ($result['is_complete'] && $result['is_draw']);
        $winnerId   = $result['winner_id'] ?? null;

        return [
            'id'                 => $game->id,
            'score_url'          => route('matches.score', $game),
            'group_name'         => $game->group?->name ?? "Group {$this->groupNumber}",
            'player_one'         => $game->playerOne?->short_name ?? 'Igrac 1',
            'player_two'         => $game->playerTwo?->short_name ?? 'Igrac 2',
            'player_one_full'    => $game->playerOne?->full_name ?? 'Igrac 1',
            'player_two_full'    => $game->playerTwo?->full_name ?? 'Igrac 2',
            'player_one_current' => (int) ($latestLog?->player_one_score ?? ($latestSet?->player_one_score ?? 0)),
            'player_two_current' => (int) ($latestLog?->player_two_score ?? ($latestSet?->player_two_score ?? 0)),
            'sets_one'           => $result['player_one_wins'],
            'sets_two'           => $result['player_two_wins'],
            'timeline'           => $orderedSets
                ->filter(fn (GameSet $set): bool => filled($set->player_one_score) && filled($set->player_two_score))
                ->map(
                    fn (GameSet $set): array => [
                        'id'    => $set->id,
                        'score' => "{$set->player_one_score}:{$set->player_two_score}",
                    ],
                )
                ->all(),
            'duration'            => $this->matchDurationLabel($game, $isLive),
            'started_at_ts'       => $game->started_at?->timestamp,
            'is_live'             => $isLive,
            'status'              => $isLive ? 'UŽIVO' : ($isFinished ? 'ZAVRŠENO' : 'NA ČEKANJU'),
            'status_class'        => $isLive ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : ($isFinished ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'),
            'status_effect_class' => $isLive ? 'tv-group-status-live' : '',
            'player_one_class'    => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
            'player_two_class'    => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
        ];
    }

    private function isLiveGame(Game $game): bool
    {
        return $game->isLive();
    }

    private function isFinishedGame(Game $game): bool
    {
        return $game->isFinished();
    }

    private function isWaitingGame(Game $game): bool
    {
        return $game->isWaiting();
    }
};
?>

@php
    $match = $this->match;
@endphp

<div class="tv-group-match flex h-full min-h-0 flex-col" wire:poll.3s>
    @if ($match)
        <a href="{{ $match['score_url'] }}" aria-label="Open match score"
            class="tv-group-grid grid h-full min-h-0 flex-1 items-stretch overflow-hidden bg-background/35 transition-colors hover:bg-background/50 focus-visible:bg-background/50 focus-visible:outline-none">
            <div
                class="tv-group-player-column tv-group-player-one flex h-full min-h-0 flex-col items-center justify-center text-center">
                <p title="{{ $match['player_one_full'] }}"
                    class="tv-group-player-name truncate whitespace-nowrap font-semibold leading-tight {{ $match['player_one_class'] }}">
                    {{ $match['player_one'] }}</p>
                <p
                    class="tv-group-point self-center text-center font-display font-normal leading-none text-foreground/80">
                    {{ $match['sets_one'] }}</p>
            </div>

            <div class="tv-group-center flex h-full min-w-40 flex-col items-center justify-center">
                <p class="tv-group-name font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ $match['group_name'] }}
                </p>
                <p class="tv-group-duration inline-flex items-center gap-1.5 text-muted-foreground text-xs"
                    x-data="{
                        startedAtTs: {{ $match['started_at_ts'] ?? 'null' }},
                        isLive: {{ $match['is_live'] ? 'true' : 'false' }},
                        now: Math.floor(Date.now() / 1000),
                        get elapsed() {
                            if (!this.isLive || this.startedAtTs === null) return null;
                            return Math.max(0, this.now - this.startedAtTs);
                        },
                        formatDuration(totalSeconds) {
                            if (totalSeconds === null || totalSeconds <= 0) {
                                return '—';
                            }

                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            const remainingSeconds = totalSeconds % 60;

                            if (hours > 0) {
                                return `${hours}:${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
                            }

                            return `${minutes}:${String(remainingSeconds).padStart(2, '0')}`;
                        },
                    }"
                    x-init="
                        const _key = '_tvTick' + {{ $groupNumber }};
                        if (window[_key]) clearInterval(window[_key]);
                        if (isLive && startedAtTs !== null) {
                            window[_key] = setInterval(() => { now = Math.floor(Date.now() / 1000); }, 1000);
                        }
                    ">
                    <x-heroicon-o-clock class="h-4 w-4" aria-hidden="true" />
                    <span x-show="!isLive" x-cloak>{{ $match['duration'] }}</span>
                    <span x-show="isLive" x-cloak x-text="formatDuration(elapsed)"></span>
                </p>
                <span
                    class="tv-group-status rounded-full px-3 py-1 font-semibold tracking-wide {{ $match['status_class'] }} {{ $match['status_effect_class'] }}">
                    {{ $match['status'] }}
                </span>
                <div
                    class="tv-group-set-score font-display font-semibold leading-none tracking-tight text-foreground text-nowrap">
                    {{ $match['player_one_current'] }} : {{ $match['player_two_current'] }}
                </div>

                <div class="tv-group-timeline flex w-full flex-wrap items-center justify-center overflow-hidden">
                    @forelse ($match['timeline'] as $timeline)
                        <span
                            class="tv-group-chip shrink-0 rounded-full bg-background/80 font-semibold text-muted-foreground"
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
                    class="tv-group-player-name truncate whitespace-nowrap font-semibold leading-tight {{ $match['player_two_class'] }}">
                    {{ $match['player_two'] }}</p>
                <p
                    class="tv-group-point self-center text-center font-display font-normal leading-none text-foreground/80">
                    {{ $match['sets_two'] }}</p>
            </div>
        </a>
    @else
        <div
            class="tv-group-empty flex min-h-0 flex-1 items-center justify-center bg-background/35 px-6 text-center text-muted-foreground">
            Nema meceva za ovu grupu.
        </div>
    @endif
</div>
