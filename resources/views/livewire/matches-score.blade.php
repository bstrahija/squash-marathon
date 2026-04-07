<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\GameLog;
use Livewire\Component;

new class extends Component {
    public ?int $gameId = null;

    public string $playerOneName = 'Player 1';

    public string $playerTwoName = 'Player 2';

    public int $playerOneScore = 0;

    public int $playerTwoScore = 0;

    public ?string $servingPlayer = null;

    public string $servingSide = 'right';

    public bool $servingPending = true;

    public bool $showStartOverlay = false;

    public bool $showRestartConfirmation = false;

    public string $roundName = '—';

    public string $groupName = '—';

    public int $playerOneSets = 0;

    public int $playerTwoSets = 0;

    /**
     * @var array<int, string>
     */
    public array $historyScores = [];

    public function mount(int $gameId): void
    {
        $this->gameId = $gameId;

        $this->syncGameState();
    }

    public function awardLeftPoint(): void
    {
        $this->appendScoreLog(GameLogSide::Left);
    }

    public function awardRightPoint(): void
    {
        $this->appendScoreLog(GameLogSide::Right);
    }

    public function selectServe(string $player): void
    {
        if (! in_array($player, ['left', 'right'], true)) {
            return;
        }

        if ($this->servingPlayer !== $player) {
            $this->servingPlayer = $player;
            $this->servingSide = 'right';
            $this->servingPending = true;

            return;
        }

        $this->servingSide = $this->toggleServeSide($this->servingSide);
    }

    public function serveButtonLabel(string $player): string
    {
        if (! in_array($player, ['left', 'right'], true)) {
            return '?';
        }

        if ($this->servingPlayer === null) {
            return '?';
        }

        if ($this->servingPlayer !== $player) {
            return '?';
        }

        $label = $this->servingSide === 'right' ? 'R' : 'L';

        if ($this->servingPending) {
            return $label.'?';
        }

        return $label;
    }

    public function undoLastLog(): void
    {
        if (! $this->gameId) {
            return;
        }

        $lastLog = GameLog::query()
            ->where('game_id', $this->gameId)
            ->orderByDesc('sequence')
            ->first();

        if (! $lastLog) {
            return;
        }

        $lastLog->delete();

        $this->syncGameState();
    }

    public function requestRestartGame(): void
    {
        if (! $this->gameId) {
            return;
        }

        $this->showRestartConfirmation = true;
    }

    public function cancelRestartGame(): void
    {
        $this->showRestartConfirmation = false;
    }

    public function confirmRestartGame(): void
    {
        if (! $this->gameId) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (! $game) {
            $this->showRestartConfirmation = false;

            return;
        }

        $game->gameLogs()->delete();

        $game
            ->forceFill([
                'started_at' => now(),
            ])
            ->save();

        $this->showRestartConfirmation = false;
        $this->syncGameState();
    }

    private function appendScoreLog(GameLogSide $side): void
    {
        if (! $this->gameId) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (! $game || ! $game->player_one_id || ! $game->player_two_id) {
            return;
        }

        if ($this->servingPlayer === null) {
            return;
        }

        $lastLog = GameLog::query()
            ->where('game_id', $game->id)
            ->orderByDesc('sequence')
            ->first();

        $nextSequence = ($lastLog?->sequence ?? 0) + 1;

        $playerOneScore = (int) ($lastLog?->player_one_score ?? 0);
        $playerTwoScore = (int) ($lastLog?->player_two_score ?? 0);

        if ($side->value === $this->servingPlayer) {
            if ($this->servingPending) {
                $this->servingPending = false;
                $this->servingSide = $this->toggleServeSide($this->servingSide);
            } else {
                $this->servingSide = $this->toggleServeSide($this->servingSide);
            }
        } else {
            $this->servingPlayer = $side->value;
            $this->servingSide = 'right';
            $this->servingPending = true;
        }

        if ($side === GameLogSide::Left) {
            $playerOneScore++;
        } else {
            $playerTwoScore++;
        }

        GameLog::query()->create([
            'game_id' => $game->id,
            'player_one_id' => $game->player_one_id,
            'player_two_id' => $game->player_two_id,
            'sequence' => $nextSequence,
            'type' => GameLogType::Score,
            'side' => $side,
            'serving_player_id' => $this->resolveServingPlayerId($game),
            'serving_side' => GameLogSide::from($this->servingSide),
            'player_one_score' => $playerOneScore,
            'player_two_score' => $playerTwoScore,
            'player_one_sets' => $this->playerOneSets,
            'player_two_sets' => $this->playerTwoSets,
        ]);

        $this->playerOneScore = $playerOneScore;
        $this->playerTwoScore = $playerTwoScore;
        array_unshift($this->historyScores, sprintf('%d - %d', $playerOneScore, $playerTwoScore));
    }

    private function syncGameState(): void
    {
        if (! $this->gameId) {
            return;
        }

        $game = Game::query()
            ->with([
                'playerOne',
                'playerTwo',
                'round',
                'group',
                'sets' => fn($query) => $query->orderBy('created_at'),
                'gameLogs' => fn($query) => $query->orderBy('sequence'),
            ])
            ->find($this->gameId);

        if (!$game) {
            return;
        }

        $this->showStartOverlay = ! $game->started_at;

        $this->playerOneName = $game->playerOne?->full_name ?? $this->playerOneName;
        $this->playerTwoName = $game->playerTwo?->full_name ?? $this->playerTwoName;
        $this->roundName = $game->round?->name ?? $this->roundName;
        $this->groupName = $game->group?->name ?? $this->groupName;

        $sets = $game->sets->filter(fn($set): bool => filled($set->player_one_score) && filled($set->player_two_score))->values();

        $this->historyScores = $game->gameLogs
            ->sortByDesc('sequence')
            ->map(
                fn($history): string => sprintf(
                    '%d - %d',
                    (int) $history->player_one_score,
                    (int) $history->player_two_score,
                ),
            )
            ->all();

        $latestLog = $game->gameLogs->last();

        $this->playerOneScore = (int) ($latestLog?->player_one_score ?? 0);
        $this->playerTwoScore = (int) ($latestLog?->player_two_score ?? 0);

        if ($latestLog) {
            $this->servingPlayer = $this->resolveServingPlayerSide($game, $latestLog->serving_player_id);
            $this->servingSide = $latestLog->serving_side?->value ?? 'right';

            $previousLog = $game->gameLogs->count() > 1
                ? $game->gameLogs->get($game->gameLogs->count() - 2)
                : null;

            $this->servingPending = $previousLog
                ? (int) $previousLog->serving_player_id !== (int) $latestLog->serving_player_id
                : false;
        } else {
            $this->servingPlayer = null;
            $this->servingSide = 'right';
            $this->servingPending = true;
        }

        $result = Game::determineMatchResultFromSetScores(
            $sets
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

        $this->playerOneSets = $result['player_one_wins'];
        $this->playerTwoSets = $result['player_two_wins'];
    }

    private function toggleServeSide(string $side): string
    {
        return $side === 'right' ? 'left' : 'right';
    }

    private function resolveServingPlayerId(Game $game): ?int
    {
        return match ($this->servingPlayer) {
            'left' => $game->player_one_id,
            'right' => $game->player_two_id,
            default => null,
        };
    }

    private function resolveServingPlayerSide(Game $game, ?int $servingPlayerId): ?string
    {
        if (! $servingPlayerId) {
            return null;
        }

        if ((int) $game->player_one_id === $servingPlayerId) {
            return 'left';
        }

        if ((int) $game->player_two_id === $servingPlayerId) {
            return 'right';
        }

        return null;
    }

    public function startMatch(): void
    {
        if (!$this->gameId) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (!$game) {
            return;
        }

        if (!filled($game->started_at)) {
            $game
                ->forceFill([
                    'started_at' => now(),
                ])
                ->save();
        }

        $this->showStartOverlay = false;
    }
};
?>

<div class="relative h-svh w-svw bg-background px-4 py-6 sm:px-6">
    @if ($showStartOverlay)
        <div class="absolute inset-0 z-50 flex items-center justify-center bg-background/90 backdrop-blur-sm">
            <button type="button" wire:click="startMatch"
                class=" cursor-pointer rounded-3xl border border-primary/30 bg-primary px-10 py-6 text-4xl font-display font-semibold text-primary-foreground shadow-lg transition hover:-translate-y-0.5">
                Start
            </button>
        </div>
    @endif

    @if ($showRestartConfirmation)
        <div class="absolute inset-0 z-[60] flex items-center justify-center bg-background/90 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-3xl border border-border bg-card p-6 shadow-xl">
                <p class="text-center text-base font-semibold text-foreground">
                    Restart this game?
                </p>
                <p class="mt-2 text-center text-sm text-muted-foreground">
                    This will clear all score history and start a fresh game clock.
                </p>

                <div class="mt-5 flex items-center justify-center gap-3">
                    <button type="button" wire:click="confirmRestartGame"
                        class="cursor-pointer rounded-xl border border-red-500/50 bg-red-500/10 px-4 py-2 text-sm font-semibold uppercase tracking-[0.08em] text-red-600 transition hover:bg-red-500/15">
                        Restart
                    </button>
                    <button type="button" wire:click="cancelRestartGame"
                        class="cursor-pointer rounded-xl border border-border bg-background px-4 py-2 text-sm font-semibold uppercase tracking-[0.08em] text-foreground transition hover:border-foreground/40">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="grid h-full w-full grid-cols-[40%_20%_40%]">
        <section class="flex h-full min-h-0 flex-col gap-3 pr-2 sm:gap-4 sm:pr-3">
            <div
                class="w-full rounded-2xl border border-primary/35 bg-primary px-4 py-6 text-center text-primary-foreground shadow-sm">
                <p class="font-display truncate whitespace-nowrap text-3xl sm:text-4xl">{{ $playerOneName }}</p>
            </div>

            <div
                class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden rounded-3xl border border-emerald-950/70 bg-slate-900 px-4 py-5 text-emerald-50 shadow-lg">
                <p class="font-display text-[clamp(5.25rem,16vw,10.5rem)] font-bold leading-none">
                    <span wire:click="awardLeftPoint" class="cursor-pointer select-none">
                        {{ $playerOneScore }}
                    </span>
                </p>

                @if ($servingPlayer === null || $servingPlayer === 'left')
                    <button type="button" wire:click="selectServe('left')"
                        class="absolute bottom-3 left-3 cursor-pointer text-6xl rounded-xl border border-slate-500/40 bg-slate-950 px-8 py-5 font-bold uppercase tracking-[0.08em] text-slate-100 transition hover:bg-slate-900">
                        {{ $this->serveButtonLabel('left') }}
                    </button>
                @endif
            </div>
        </section>

        <section class="flex h-full min-h-0 flex-col gap-3 px-1 sm:gap-4 sm:px-2">
            <div class="rounded-2xl border border-border/70 bg-card px-3 py-4 text-center shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">{{ $roundName }}
                </p>
                <p class="mt-1 text-sm font-semibold text-foreground">{{ $groupName }}</p>
                <p class="font-display mt-1 text-3xl text-foreground sm:text-4xl">
                    {{ $playerOneSets }} - {{ $playerTwoSets }}
                </p>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-2xl border border-border/70 bg-card p-2 shadow-sm">
                <ul class="space-y-1.5">
                    @forelse ($historyScores as $index => $historyScore)
                        <li wire:key="history-score-{{ $index }}"
                            class="rounded-lg border border-border/60 bg-background px-2 py-1.5 text-center text-sm font-semibold text-foreground sm:text-base">
                            {{ $historyScore }}
                        </li>
                    @empty
                        <li
                            class="rounded-lg border border-border/60 bg-background px-2 py-1.5 text-center text-sm font-semibold text-muted-foreground sm:text-base">
                            Nema povijesti bodova.
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" wire:click="undoLastLog"
                    class="rounded-2xl border border-border bg-card px-3 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-foreground shadow-sm transition hover:-translate-y-0.5 hover:border-foreground/40">
                    Undo
                </button>

                <button type="button" wire:click="requestRestartGame"
                    class="rounded-2xl border border-red-500/50 bg-red-500/10 px-3 py-3 text-sm font-semibold uppercase tracking-[0.08em] text-red-600 shadow-sm transition hover:-translate-y-0.5 hover:bg-red-500/15">
                    Restart
                </button>
            </div>
        </section>

        <section class="flex h-full min-h-0 flex-col gap-3 pl-2 sm:gap-4 sm:pl-3">
            <div
                class="w-full rounded-2xl border border-primary/35 bg-primary px-4 py-6 text-center text-primary-foreground shadow-sm">
                <p class="font-display truncate whitespace-nowrap text-2xl sm:text-3xl">{{ $playerTwoName }}</p>
            </div>

            <div
                class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden rounded-3xl border border-emerald-950/70 bg-slate-900 px-4 py-5 text-emerald-50 shadow-lg">
                <p class="font-display text-[clamp(5.25rem,16vw,10.5rem)] font-bold leading-none">
                    <span wire:click="awardRightPoint" class="cursor-pointer select-none">
                        {{ $playerTwoScore }}
                    </span>
                </p>

                @if ($servingPlayer === null || $servingPlayer === 'right')
                    <button type="button" wire:click="selectServe('right')"
                        class="absolute bottom-3 right-3 cursor-pointer text-6xl rounded-xl border border-slate-500/40 bg-slate-950 px-8 py-5 font-bold uppercase tracking-[0.08em] text-slate-100 transition hover:bg-slate-900">
                        {{ $this->serveButtonLabel('right') }}
                    </button>
                @endif
            </div>
        </section>
    </div>
</div>
