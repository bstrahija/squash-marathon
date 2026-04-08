<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\GameLog;
use App\Models\Set;
use Illuminate\Support\Facades\DB;
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

    public bool $showNextSetOverlay = false;

    public bool $showMatchDoneOverlay = false;

    public int $matchDoneBurst = 0;

    public string $roundName = '—';

    public string $groupName = '—';

    public int $bestOf = 2;

    public int $playerOneSets = 0;

    public int $playerTwoSets = 0;

    public string $matchWinnerName = '—';

    public string $matchLoserName = '—';

    public string $matchFinalResult = '—';

    public string $matchDurationLabel = '—';

    public bool $matchIsDraw = false;

    /**
     * @var array<int, string>
     */
    public array $setPointBadges = [];

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
        if (!in_array($player, ['left', 'right'], true)) {
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
        if (!in_array($player, ['left', 'right'], true)) {
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
            return $label . '?';
        }

        return $label;
    }

    public function undoLastLog(): void
    {
        if (!$this->gameId) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (!$game) {
            return;
        }

        if ($this->canNavigateBackToPreviousSet($game)) {
            $this->navigateBackToPreviousSet($game);

            $this->showMatchDoneOverlay = false;
            $this->showNextSetOverlay = false;
            $this->syncGameState();

            return;
        }

        $lastLog = GameLog::query()->where('game_id', $this->gameId)->orderByDesc('sequence')->first();

        if (!$lastLog) {
            if ($this->canNavigateBackToPreviousSet($game)) {
                $this->navigateBackToPreviousSet($game);

                $this->showMatchDoneOverlay = false;
                $this->showNextSetOverlay = false;
                $this->syncGameState();

                return;
            }

            return;
        }

        $lastPlayerOneScore = (int) $lastLog->player_one_score;
        $lastPlayerTwoScore = (int) $lastLog->player_two_score;

        $lastLog->delete();

        $this->rollbackSetIfLastPointClosedIt($game, $lastPlayerOneScore, $lastPlayerTwoScore);

        $this->showMatchDoneOverlay = false;
        $this->showNextSetOverlay = false;

        $this->syncGameState();

        $refreshedGame = Game::query()->find($this->gameId);

        if ($refreshedGame && $this->canNavigateBackToPreviousSet($refreshedGame)) {
            $this->showNextSetOverlay = true;
        }
    }

    public function requestRestartGame(): void
    {
        if (!$this->gameId) {
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
        if (!$this->gameId) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (!$game) {
            $this->showRestartConfirmation = false;

            return;
        }

        DB::transaction(function () use ($game): void {
            $startedAt = now();

            $game->gameLogs()->delete();
            $game->sets()->delete();

            $game
                ->forceFill([
                    'player_one_sets' => 0,
                    'player_two_sets' => 0,
                    'winner_id' => null,
                    'is_draw' => false,
                    'started_at' => $startedAt,
                    'finished_at' => null,
                    'duration_seconds' => null,
                ])
                ->save();

            $this->ensureActiveSet($game, $startedAt);
        });

        $this->showRestartConfirmation = false;
        $this->showNextSetOverlay = false;
        $this->showMatchDoneOverlay = false;
        $this->syncGameState();
    }

    public function startNextSet(): void
    {
        if (!$this->gameId || !$this->showNextSetOverlay) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (!$game) {
            return;
        }

        $completedSets = Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count();

        if ($completedSets >= $game->best_of) {
            $this->showNextSetOverlay = false;
            $this->openMatchDoneOverlay();

            return;
        }

        DB::transaction(function () use ($game): void {
            $game->gameLogs()->delete();
            $this->ensureActiveSet($game, now());
        });

        $this->showNextSetOverlay = false;
        $this->showMatchDoneOverlay = false;
        $this->syncGameState();
    }

    public function finishMatch()
    {
        return redirect()->route('matches.index');
    }

    private function appendScoreLog(GameLogSide $side): void
    {
        if (!$this->gameId) {
            return;
        }

        if ($this->showStartOverlay || $this->showRestartConfirmation || $this->showNextSetOverlay || $this->showMatchDoneOverlay) {
            return;
        }

        $game = Game::query()->find($this->gameId);

        if (!$game || !$game->player_one_id || !$game->player_two_id) {
            return;
        }

        if ($this->servingPlayer === null) {
            $this->servingPlayer = $side->value;
            $this->servingSide = 'right';
            $this->servingPending = true;
        }

        $lastLog = GameLog::query()->where('game_id', $game->id)->orderByDesc('sequence')->first();

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

        if ($this->isSetFinished($playerOneScore, $playerTwoScore)) {
            $result = $this->completeCurrentSet($game, $playerOneScore, $playerTwoScore);

            $this->syncGameState();

            if ($result['is_complete']) {
                $this->openMatchDoneOverlay();
                $this->showNextSetOverlay = false;

                return;
            }

            $this->showNextSetOverlay = true;
            $this->showMatchDoneOverlay = false;
        }
    }

    private function syncGameState(): void
    {
        if (!$this->gameId) {
            return;
        }

        $game = Game::query()
            ->with(['playerOne', 'playerTwo', 'round', 'group', 'sets' => fn($query) => $query->orderBy('created_at'), 'gameLogs' => fn($query) => $query->orderBy('sequence')])
            ->find($this->gameId);

        if (!$game) {
            return;
        }

        $this->showStartOverlay = !$game->started_at;

        $this->playerOneName = $game->playerOne?->full_name ?? $this->playerOneName;
        $this->playerTwoName = $game->playerTwo?->full_name ?? $this->playerTwoName;
        $this->roundName = $game->round?->name ?? $this->roundName;
        $this->groupName = $game->group?->name ?? $this->groupName;
        $this->bestOf = (int) ($game->best_of ?? $this->bestOf);

        $completedSets = $game->sets->filter(fn($set): bool => filled($set->finished_at) && filled($set->player_one_score) && filled($set->player_two_score))->values();
        $activeSet = $game->sets->first(fn($set): bool => blank($set->finished_at));

        $this->setPointBadges = $completedSets->values()->map(fn($set): string => sprintf('%d:%d', (int) $set->player_one_score, (int) $set->player_two_score))->all();

        $this->historyScores = $game->gameLogs->sortByDesc('sequence')->map(fn($history): string => sprintf('%d - %d', (int) $history->player_one_score, (int) $history->player_two_score))->all();

        $latestLog = $game->gameLogs->last();

        $this->playerOneScore = (int) ($latestLog?->player_one_score ?? 0);
        $this->playerTwoScore = (int) ($latestLog?->player_two_score ?? 0);

        if ($latestLog) {
            $this->servingPlayer = $this->resolveServingPlayerSide($game, $latestLog->serving_player_id);
            $this->servingSide = $latestLog->serving_side?->value ?? 'right';

            $previousLog = $game->gameLogs->count() > 1 ? $game->gameLogs->get($game->gameLogs->count() - 2) : null;

            $this->servingPending = $previousLog ? (int) $previousLog->serving_player_id !== (int) $latestLog->serving_player_id : false;
        } else {
            $this->servingPlayer = null;
            $this->servingSide = 'right';
            $this->servingPending = true;
        }

        $result = Game::determineMatchResultFromSetScores(
            $completedSets
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

        $this->playerOneSets = (int) ($game->player_one_sets ?? $result['player_one_wins']);
        $this->playerTwoSets = (int) ($game->player_two_sets ?? $result['player_two_wins']);

        if ($result['is_complete']) {
            $this->matchIsDraw = (bool) $result['is_draw'];
            $this->matchWinnerName = $result['is_draw'] ? 'Draw' : ($result['winner_id'] === $game->player_one_id ? $this->playerOneName : $this->playerTwoName);
            $this->matchLoserName = $result['is_draw'] ? '—' : ($result['winner_id'] === $game->player_one_id ? $this->playerTwoName : $this->playerOneName);
            $this->matchFinalResult = sprintf('%d - %d', $this->playerOneSets, $this->playerTwoSets);
            $this->matchDurationLabel = $this->formatDuration($game->duration_seconds);
        } else {
            $this->matchIsDraw = false;
            $this->matchWinnerName = '—';
            $this->matchLoserName = '—';
            $this->matchFinalResult = '—';
            $this->matchDurationLabel = '—';
        }

        if (!$this->showMatchDoneOverlay && !$this->showRestartConfirmation && !$this->showStartOverlay) {
            if ((bool) $result['is_complete'] && $game->gameLogs->isNotEmpty()) {
                $this->openMatchDoneOverlay();
            }
        }

        if (!$this->showNextSetOverlay && !$this->showRestartConfirmation && !$this->showStartOverlay) {
            $this->showNextSetOverlay = !$result['is_complete'] && $completedSets->isNotEmpty() && $activeSet === null && $game->gameLogs->isEmpty();
        }
    }

    private function isSetFinished(int $playerOneScore, int $playerTwoScore): bool
    {
        $maxScore = max($playerOneScore, $playerTwoScore);
        $difference = abs($playerOneScore - $playerTwoScore);

        return $maxScore >= 11 && $difference >= 2;
    }

    /**
     * @return array{
     *     is_complete: bool,
     *     is_draw: bool,
     *     winner_id: int|null,
     *     player_one_wins: int,
     *     player_two_wins: int
     * }
     */
    private function completeCurrentSet(Game $game, int $playerOneScore, int $playerTwoScore): array
    {
        $result = [
            'is_complete' => false,
            'is_draw' => false,
            'winner_id' => null,
            'player_one_wins' => 0,
            'player_two_wins' => 0,
        ];

        DB::transaction(function () use ($game, $playerOneScore, $playerTwoScore, &$result): void {
            $freshGame = Game::query()->find($game->id);

            if (!$freshGame) {
                return;
            }

            $now = now();
            $activeSet = $this->ensureActiveSet($freshGame, $freshGame->started_at ?? $now);

            $activeSet
                ->forceFill([
                    'player_one_score' => $playerOneScore,
                    'player_two_score' => $playerTwoScore,
                    'finished_at' => $now,
                ])
                ->save();

            $result = $this->persistGameStateFromSets($freshGame, $now);
        });

        return $result;
    }

    /**
     * @return array{
     *     is_complete: bool,
     *     is_draw: bool,
     *     winner_id: int|null,
     *     player_one_wins: int,
     *     player_two_wins: int
     * }
     */
    private function persistGameStateFromSets(Game $game, ?\DateTimeInterface $completedAt = null): array
    {
        $completedSets = Set::query()
            ->where('game_id', $game->id)
            ->whereNotNull('finished_at')
            ->orderBy('id')
            ->get(['player_one_score', 'player_two_score']);

        $result = Game::determineMatchResultFromSetScores(
            $completedSets
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

        $game
            ->forceFill([
                'player_one_sets' => $result['player_one_wins'],
                'player_two_sets' => $result['player_two_wins'],
                'winner_id' => $result['winner_id'],
                'is_draw' => $result['is_draw'],
                'finished_at' => $result['is_complete'] ? $completedAt ?? now() : null,
            ])
            ->save();

        return $result;
    }

    private function ensureActiveSet(Game $game, \DateTimeInterface $startedAt): Set
    {
        $existingSet = Set::query()->where('game_id', $game->id)->whereNull('finished_at')->orderByDesc('id')->first();

        if ($existingSet) {
            return $existingSet;
        }

        return Set::query()->create([
            'game_id' => $game->id,
            'round_id' => $game->round_id,
            'group_id' => $game->group_id,
            'player_one_id' => $game->player_one_id,
            'player_two_id' => $game->player_two_id,
            'started_at' => $startedAt,
            'finished_at' => null,
            'player_one_score' => null,
            'player_two_score' => null,
        ]);
    }

    private function rollbackSetIfLastPointClosedIt(Game $game, int $lastPlayerOneScore, int $lastPlayerTwoScore): void
    {
        DB::transaction(function () use ($game, $lastPlayerOneScore, $lastPlayerTwoScore): void {
            $latestCompletedSet = Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->orderByDesc('id')->first();

            if (!$latestCompletedSet) {
                return;
            }

            if ((int) $latestCompletedSet->player_one_score !== $lastPlayerOneScore || (int) $latestCompletedSet->player_two_score !== $lastPlayerTwoScore) {
                return;
            }

            $setStartedAt = $latestCompletedSet->started_at ?? now();
            $latestCompletedSet->delete();

            $freshGame = Game::query()->find($game->id);

            if (!$freshGame) {
                return;
            }

            $this->ensureActiveSet($freshGame, $setStartedAt);
            $this->persistGameStateFromSets($freshGame);
        });
    }

    private function canNavigateBackToPreviousSet(Game $game): bool
    {
        $hasCurrentSet = Set::query()->where('game_id', $game->id)->whereNull('finished_at')->exists();

        $hasCompletedSet = Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->exists();

        $hasCurrentSetLogs = GameLog::query()->where('game_id', $game->id)->where('player_one_sets', (int) $game->player_one_sets)->where('player_two_sets', (int) $game->player_two_sets)->exists();

        return $hasCurrentSet && $hasCompletedSet && !$hasCurrentSetLogs;
    }

    private function navigateBackToPreviousSet(Game $game): void
    {
        DB::transaction(function () use ($game): void {
            $activeSet = Set::query()->where('game_id', $game->id)->whereNull('finished_at')->orderByDesc('id')->first();

            $latestCompletedSet = Set::query()->where('game_id', $game->id)->whereNotNull('finished_at')->orderByDesc('id')->first();

            if (!$activeSet || !$latestCompletedSet) {
                return;
            }

            $playerOneScore = (int) ($latestCompletedSet->player_one_score ?? 0);
            $playerTwoScore = (int) ($latestCompletedSet->player_two_score ?? 0);
            $setStartedAt = $latestCompletedSet->started_at ?? now();

            if ($playerOneScore >= $playerTwoScore) {
                $playerOneScore = max(0, $playerOneScore - 1);
                $scoringSide = GameLogSide::Left;
            } else {
                $playerTwoScore = max(0, $playerTwoScore - 1);
                $scoringSide = GameLogSide::Right;
            }

            $activeSet->delete();
            $latestCompletedSet->delete();

            $freshGame = Game::query()->find($game->id);

            if (!$freshGame) {
                return;
            }

            Set::query()->create([
                'game_id' => $freshGame->id,
                'round_id' => $freshGame->round_id,
                'group_id' => $freshGame->group_id,
                'player_one_id' => $freshGame->player_one_id,
                'player_two_id' => $freshGame->player_two_id,
                'started_at' => $setStartedAt,
                'finished_at' => null,
                'player_one_score' => null,
                'player_two_score' => null,
            ]);

            $result = $this->persistGameStateFromSets($freshGame);

            $nextSequence = ((int) (GameLog::query()->where('game_id', $freshGame->id)->max('sequence') ?? 0)) + 1;

            GameLog::query()->create([
                'game_id' => $freshGame->id,
                'player_one_id' => $freshGame->player_one_id,
                'player_two_id' => $freshGame->player_two_id,
                'sequence' => $nextSequence,
                'type' => GameLogType::Score,
                'side' => $scoringSide,
                'serving_player_id' => null,
                'serving_side' => null,
                'player_one_score' => $playerOneScore,
                'player_two_score' => $playerTwoScore,
                'player_one_sets' => $result['player_one_wins'],
                'player_two_sets' => $result['player_two_wins'],
            ]);
        });
    }

    private function openMatchDoneOverlay(): void
    {
        $this->showMatchDoneOverlay = true;
        $this->matchDoneBurst++;
    }

    private function formatDuration(?int $durationSeconds): string
    {
        if ($durationSeconds === null) {
            return '—';
        }

        $hours = intdiv($durationSeconds, 3600);
        $minutes = intdiv($durationSeconds % 3600, 60);
        $seconds = $durationSeconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
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
        if (!$servingPlayerId) {
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
            $startedAt = now();

            $game
                ->forceFill([
                    'started_at' => $startedAt,
                    'finished_at' => null,
                ])
                ->save();

            $this->ensureActiveSet($game, $startedAt);
        } else {
            $this->ensureActiveSet($game, $game->started_at);
        }

        $this->showStartOverlay = false;
        $this->showNextSetOverlay = false;
        $this->showMatchDoneOverlay = false;
        $this->syncGameState();
    }
};
?>

<div class="relative h-svh w-svw bg-background px-4 py-6 sm:px-6">
    @if ($showStartOverlay)
        <div class="absolute inset-0 z-50 flex items-center justify-center bg-background/90 backdrop-blur-sm">
            <div class="w-full max-w-2xl rounded-3xl border border-border bg-card p-6 shadow-xl sm:p-8">
                <p class="text-center text-sm uppercase tracking-[0.16em] text-muted-foreground">
                    Početak meča
                </p>

                <p class="mt-2 text-center text-4xl font-display font-semibold text-foreground sm:text-5xl">
                    {{ $playerOneName }}
                </p>
                <p class="mt-1 text-center text-lg font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                    protiv
                </p>
                <p class="mt-1 text-center text-4xl font-display font-semibold text-foreground sm:text-5xl">
                    {{ $playerTwoName }}
                </p>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                    <span
                        class="rounded-full border border-border/70 bg-background/80 px-4 py-2 text-sm font-semibold text-foreground">
                        {{ $roundName }}
                    </span>
                    <span
                        class="rounded-full border border-border/70 bg-background/80 px-4 py-2 text-sm font-semibold text-foreground">
                        {{ $groupName }}
                    </span>
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="button" wire:click="startMatch"
                        class="cursor-pointer rounded-3xl border border-primary/30 bg-primary px-10 py-6 text-4xl font-display font-semibold text-primary-foreground shadow-lg transition hover:-translate-y-0.5">
                        Start
                    </button>
                </div>
            </div>
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

    @if ($showNextSetOverlay)
        <div class="absolute inset-0 z-55 flex items-center justify-center bg-background/90 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-3xl border border-border bg-card p-6 shadow-xl">
                <p class="text-center text-base font-semibold text-foreground">
                    Set završen
                </p>

                <div class="mt-5 flex items-center justify-center gap-3">
                    <button type="button" wire:click="startNextSet"
                        class="cursor-pointer rounded-2xl border border-primary/30 bg-primary px-5 py-3 text-base font-display font-semibold uppercase tracking-[0.08em] text-primary-foreground shadow-lg transition hover:-translate-y-0.5">
                        Sljedeći set
                    </button>
                    <button type="button" wire:click="undoLastLog"
                        class="cursor-pointer rounded-2xl border border-border bg-background px-5 py-3 text-base font-semibold uppercase tracking-[0.08em] text-foreground transition hover:border-foreground/40">
                        Poništi
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showMatchDoneOverlay)
        <div class="absolute inset-0 z-55 flex items-center justify-center bg-background/90 backdrop-blur-sm"
            data-match-done-overlay="true" x-data="{ burst: {{ $matchDoneBurst }} }" x-init="$nextTick(() => { if (window.launchMatchDoneConfetti) { window.launchMatchDoneConfetti(); } })">

            <div class="relative w-full max-w-2xl rounded-3xl border border-border bg-card p-6 shadow-xl sm:p-8">
                @if ($matchIsDraw)
                    <p class="text-center font-display text-4xl font-semibold text-foreground sm:text-5xl">
                        Remi
                    </p>
                    <div class="mx-auto mt-3 h-px w-full max-w-md bg-border/70"></div>
                    <p class="mt-4 text-center text-lg font-display font-semibold text-foreground sm:text-xl">
                        {{ $playerOneName }}
                    </p>
                    <p class="mt-1 text-center text-lg font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                        VS
                    </p>
                    <p class="mt-1 text-center text-lg font-display font-semibold text-foreground sm:text-xl">
                        {{ $playerTwoName }}
                    </p>
                @else
                    <p class="text-center text-sm uppercase tracking-[0.16em] text-muted-foreground">
                        Pobjednik
                    </p>
                    <p class="mt-1 text-center text-4xl font-display font-semibold text-foreground sm:text-5xl">
                        {{ $matchWinnerName }}
                    </p>

                    <p class="mt-5 text-center text-sm uppercase tracking-[0.16em] text-muted-foreground">
                        Luzer
                    </p>
                    <p class="mt-1 text-center text-2xl font-display font-semibold text-foreground sm:text-3xl">
                        {{ $matchLoserName }}
                    </p>
                @endif

                <div class="mt-6">
                    <p class="text-center text-sm uppercase tracking-[0.16em] text-muted-foreground">
                        Konačni rezultat
                    </p>
                    <p class="mt-1 text-center text-3xl font-display font-semibold text-foreground sm:text-4xl">
                        {{ $matchFinalResult }}
                    </p>
                </div>

                <div class="mt-6">
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        @foreach ($setPointBadges as $badge)
                            <span
                                class="rounded-full border border-border/70 bg-background/80 px-4 py-2 text-sm font-semibold text-foreground">
                                {{ $badge }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex flex-col items-center gap-3">
                    <button type="button" wire:click="finishMatch"
                        class="w-full max-w-xs cursor-pointer rounded-2xl border border-primary/30 bg-primary px-8 py-4 text-2xl font-display font-semibold uppercase tracking-[0.08em] text-primary-foreground shadow-lg transition hover:-translate-y-0.5">
                        Završi
                    </button>

                    <button type="button" wire:click="undoLastLog"
                        class="cursor-pointer rounded-xl border border-border bg-background px-4 py-2 text-sm font-semibold uppercase tracking-[0.08em] text-foreground transition hover:border-foreground/40">
                        Poništi
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
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                    {{ $roundName }}
                </p>
                <p class="mt-1 text-sm font-semibold text-foreground">
                    {{ $groupName }}
                    | Best of {{ $bestOf }}
                </p>
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
