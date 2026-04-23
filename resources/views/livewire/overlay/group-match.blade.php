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
            ->with(['sets', 'playerOne', 'playerTwo', 'group', 'gameLogs' => fn ($q) => $q->orderBy('sequence')])
            ->where('event_id', $event->id)
            ->whereHas('group', fn ($q) => $q->where('number', $this->groupNumber))
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $liveGame = $games->filter(fn (Game $game): bool => $game->isLive())->sortByDesc('id')->first();

        if ($liveGame) {
            return $this->mapGame($liveGame);
        }

        $latestGame = $games->sortByDesc('id')->first();

        return $latestGame ? $this->mapGame($latestGame) : null;
    }

    private function mapGame(Game $game): array
    {
        $orderedSets = $game->sets->sortBy('created_at')->values();
        $latestSet   = $orderedSets->last();
        $latestLog   = $game->gameLogs->last();

        $result = $game->resultFromSets();

        $isLive     = $game->isLive();
        $isFinished = $game->isFinished();
        $isDraw     = (bool) ($result['is_complete'] && $result['is_draw']);
        $winnerId   = $result['winner_id'] ?? null;

        $servingPlayerId = $latestLog?->serving_player_id;

        return [
            'id'                 => $game->id,
            'group_name'         => $game->group?->name ?? "Grupa {$this->groupNumber}",
            'player_one'         => $game->playerOne?->short_name ?? 'Igrač 1',
            'player_two'         => $game->playerTwo?->short_name ?? 'Igrač 2',
            'player_one_current' => (int) ($latestLog?->player_one_score ?? ($latestSet?->player_one_score ?? 0)),
            'player_two_current' => (int) ($latestLog?->player_two_score ?? ($latestSet?->player_two_score ?? 0)),
            'sets_one'           => $result['player_one_wins'],
            'sets_two'           => $result['player_two_wins'],
            'serves_player_one'  => $isLive && $servingPlayerId !== null && $servingPlayerId === $game->player_one_id,
            'serves_player_two'  => $isLive && $servingPlayerId !== null && $servingPlayerId === $game->player_two_id,
            'duration'           => $this->matchDurationLabel($game, $isLive),
            'started_at_ts'      => $game->started_at?->timestamp,
            'finished_at_ts'     => $game->finished_at?->timestamp,
            'is_live'            => $isLive,
            'is_finished'        => $isFinished,
            'status'             => $isLive ? 'UŽIVO' : ($isFinished ? 'ZAVRŠENO' : 'NA ČEKANJU'),
            'status_class'       => $isLive ? 'bg-emerald-500/80' : ($isFinished ? 'bg-sky-500/80' : 'bg-amber-500/80'),
            'player_one_class'   => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
            'player_two_class'   => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
            'timeline'           => $orderedSets
                ->filter(fn (GameSet $set): bool => filled($set->player_one_score) && filled($set->player_two_score))
                ->map(fn (GameSet $set): array => [
                    'id'    => $set->id,
                    'score' => "{$set->player_one_score}:{$set->player_two_score}",
                ])
                ->all(),
        ];
    }
};
?>

@php $match = $this->match; @endphp

<div class="fixed inset-x-0 bottom-0 flex justify-center pb-16 px-10" wire:poll.3s
    @if ($match)
        data-score-a="{{ $match['player_one_current'] }}"
        data-score-b="{{ $match['player_two_current'] }}"
        data-is-live="{{ $match['is_live'] ? '1' : '0' }}"
        data-started-at="{{ $match['started_at_ts'] ?? '' }}"
        data-finished-at="{{ $match['finished_at_ts'] ?? '' }}"
        data-duration="{{ $match['duration'] }}"
    @endif
    x-data="{
        flashA: false,
        flashB: false,
        currentScoreA: {{ $match['player_one_current'] ?? 0 }},
        currentScoreB: {{ $match['player_two_current'] ?? 0 }},
        isLive: {{ ($match && $match['is_live']) ? 'true' : 'false' }},
        startedAtTs: {{ $match['started_at_ts'] ?? 'null' }},
        finishedAtTs: {{ $match['finished_at_ts'] ?? 'null' }},
        staticDuration: '{{ $match['duration'] ?? '—' }}',
        now: Math.floor(Date.now() / 1000),
        intervalId: null,
        observer: null,
        get elapsed() {
            if (!this.isLive || this.startedAtTs === null || this.finishedAtTs !== null) return null;
            return Math.max(0, this.now - this.startedAtTs);
        },
        formatDuration(totalSeconds) {
            if (totalSeconds === null || totalSeconds <= 0) return '—';
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const secs = totalSeconds % 60;
            if (hours > 0) {
                return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            }
            return `${minutes}:${String(secs).padStart(2, '0')}`;
        },
        startTick() {
            if (this.intervalId !== null) {
                return;
            }

            if (!this.isLive || this.startedAtTs === null || this.finishedAtTs !== null) {
                return;
            }

            this.intervalId = window.setInterval(() => {
                this.now = Math.floor(Date.now() / 1000);

                if (!this.isLive || this.finishedAtTs !== null) {
                    this.stopTick();
                }
            }, 1000);
        },
        stopTick() {
            if (this.intervalId === null) {
                return;
            }

            window.clearInterval(this.intervalId);
            this.intervalId = null;
        },
        syncFromServer() {
            const scoreA = Number(this.$el.dataset.scoreA);
            if (!Number.isNaN(scoreA) && scoreA !== this.currentScoreA) {
                this.currentScoreA = scoreA;
                this.flashA = true;
                setTimeout(() => {
                    this.flashA = false;
                }, 350);
            }

            const scoreB = Number(this.$el.dataset.scoreB);
            if (!Number.isNaN(scoreB) && scoreB !== this.currentScoreB) {
                this.currentScoreB = scoreB;
                this.flashB = true;
                setTimeout(() => {
                    this.flashB = false;
                }, 350);
            }

            const startedAtRaw = this.$el.dataset.startedAt;
            const parsedStartedAt = startedAtRaw === '' ? null : Number(startedAtRaw);
            this.startedAtTs = Number.isNaN(parsedStartedAt) ? null : parsedStartedAt;

            const finishedAtRaw = this.$el.dataset.finishedAt;
            const parsedFinishedAt = finishedAtRaw === '' ? null : Number(finishedAtRaw);
            this.finishedAtTs = Number.isNaN(parsedFinishedAt) ? null : parsedFinishedAt;

            this.isLive = this.$el.dataset.isLive === '1' && this.finishedAtTs === null;
            this.staticDuration = this.$el.dataset.duration || '—';

            if (this.isLive && this.startedAtTs !== null) {
                this.startTick();
            } else {
                this.stopTick();
            }
        },
        teardown() {
            this.stopTick();

            if (this.observer !== null) {
                this.observer.disconnect();
                this.observer = null;
            }
        },
        init() {
            this.syncFromServer();

            this.observer = new MutationObserver(() => this.syncFromServer());
            this.observer.observe(this.$el, {
                attributes: true,
                attributeFilter: ['data-score-a', 'data-score-b', 'data-is-live', 'data-started-at', 'data-finished-at', 'data-duration'],
            });

            this.$el.addEventListener('alpine:destroy', () => this.teardown(), { once: true });
        },
    }"
    x-init="init()">
    @if ($match)
        <div class="flex w-full max-w-3xl flex-col items-center gap-2">

            {{-- Completed set scores --}}
            @if (!empty($match['timeline']))
                <div class="flex items-center gap-2">
                    @foreach ($match['timeline'] as $set)
                        <span
                            class="rounded-full bg-black/55 px-3 py-1 text-xs font-bold text-white/75 shadow-lg backdrop-blur-sm"
                            wire:key="overlay-set-{{ $match['id'] }}-{{ $set['id'] }}">
                            {{ $set['score'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Main scoreboard bar --}}
            <div class="flex w-full items-stretch overflow-hidden rounded-2xl shadow-2xl">

                {{-- Player A --}}
                <div class="flex min-w-0 flex-1 items-center gap-3 bg-black/65 px-5 py-4 backdrop-blur-sm">
                    {{-- Serve indicator --}}
                    <div class="h-3 w-3 shrink-0 rounded-full transition-all duration-300
                        {{ $match['serves_player_one'] ? 'bg-yellow-400 shadow-[0_0_10px_3px_rgba(250,204,21,0.7)]' : 'bg-transparent' }}">
                    </div>
                    {{-- Name --}}
                    <span class="truncate text-lg font-bold leading-tight text-white drop-shadow overlay-player-one-name">
                        {{ $match['player_one'] }}
                    </span>
                    {{-- Sets won --}}
                    <div class="ml-auto flex shrink-0">
                        <span @class([
                            'flex h-5 w-5 items-center justify-center rounded text-xs font-black shadow',
                            'bg-white/20 text-white/40' => $match['sets_one'] === 0,
                            'bg-white/90 text-black'   => $match['sets_one'] > 0,
                        ])>{{ $match['sets_one'] }}</span>
                    </div>
                </div>

                {{-- Score --}}
                <div class="flex shrink-0 flex-col items-center bg-black/80 px-5 py-4 backdrop-blur-sm">
                    <div class="flex items-center">
                        <span :class="flashA ? 'scale-125 text-yellow-300' : 'scale-100 text-white'"
                            class="font-display tabular-nums text-6xl font-black leading-none drop-shadow-lg transition-all duration-200 overlay-score-a">
                            {{ $match['player_one_current'] }}
                        </span>
                        <span class="mx-2 text-2xl font-thin leading-none text-white/30">:</span>
                        <span :class="flashB ? 'scale-125 text-yellow-300' : 'scale-100 text-white'"
                            class="font-display tabular-nums text-6xl font-black leading-none drop-shadow-lg transition-all duration-200 overlay-score-b">
                            {{ $match['player_two_current'] }}
                        </span>
                    </div>
                    <div class="mt-1.5 flex items-center gap-1 text-xs text-white/50">
                        <x-heroicon-o-clock class="h-3 w-3 shrink-0" aria-hidden="true" />
                        <span x-show="!isLive" x-cloak x-text="staticDuration"></span>
                        <span x-show="isLive" x-cloak x-text="formatDuration(elapsed)"></span>
                    </div>
                </div>

                {{-- Player B --}}
                <div class="flex min-w-0 flex-1 flex-row-reverse items-center gap-3 bg-black/65 px-5 py-4 backdrop-blur-sm">
                    {{-- Serve indicator --}}
                    <div class="h-3 w-3 shrink-0 rounded-full transition-all duration-300
                        {{ $match['serves_player_two'] ? 'bg-yellow-400 shadow-[0_0_10px_3px_rgba(250,204,21,0.7)]' : 'bg-transparent' }}">
                    </div>
                    {{-- Name --}}
                    <span class="truncate text-right text-lg font-bold leading-tight text-white drop-shadow overlay-player-two-name">
                        {{ $match['player_two'] }}
                    </span>
                    {{-- Sets won --}}
                    <div class="mr-auto flex shrink-0">
                        <span @class([
                            'flex h-5 w-5 items-center justify-center rounded text-xs font-black shadow',
                            'bg-white/20 text-white/40' => $match['sets_two'] === 0,
                            'bg-white/90 text-black'   => $match['sets_two'] > 0,
                        ])>{{ $match['sets_two'] }}</span>
                    </div>
                </div>

            </div>

            {{-- Bottom strip: group name + status --}}
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-black/55 px-4 py-1 text-xs font-semibold uppercase tracking-widest text-white/60 shadow backdrop-blur-sm">
                    {{ $match['group_name'] }}
                </span>
                <span class="rounded-full px-4 py-1 text-xs font-bold uppercase tracking-widest text-white shadow backdrop-blur-sm {{ $match['status_class'] }}">
                    {{ $match['status'] }}
                </span>
            </div>

        </div>
    @endif
</div>
