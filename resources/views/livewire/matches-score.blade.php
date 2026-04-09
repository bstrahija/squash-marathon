<?php

use App\Enums\GameLogSide;
use App\Enums\GameLogType;
use App\Models\Game;
use App\Models\GameLog;
use App\Models\GameSet;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component {
    public ?int $gameId = null;

    public string $playerOneName = 'Player 1';

    public string $playerTwoName = 'Player 2';

    public string $playerOneShortName = 'Player 1';

    public string $playerTwoShortName = 'Player 2';

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

        $completedSets = GameSet::query()->where('game_id', $game->id)->whereNotNull('finished_at')->count();

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
        $this->playerOneShortName = $game->playerOne?->short_name ?? $this->playerOneShortName;
        $this->playerTwoShortName = $game->playerTwo?->short_name ?? $this->playerTwoShortName;
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
        $completedSets = GameSet::query()
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

    private function ensureActiveSet(Game $game, \DateTimeInterface $startedAt): GameSet
    {
        $existingSet = GameSet::query()->where('game_id', $game->id)->whereNull('finished_at')->orderByDesc('id')->first();

        if ($existingSet) {
            return $existingSet;
        }

        return GameSet::query()->create([
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
            $latestCompletedSet = GameSet::query()->where('game_id', $game->id)->whereNotNull('finished_at')->orderByDesc('id')->first();

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
        $hasCurrentSet = GameSet::query()->where('game_id', $game->id)->whereNull('finished_at')->exists();

        $hasCompletedSet = GameSet::query()->where('game_id', $game->id)->whereNotNull('finished_at')->exists();

        $hasCurrentSetLogs = GameLog::query()->where('game_id', $game->id)->where('player_one_sets', (int) $game->player_one_sets)->where('player_two_sets', (int) $game->player_two_sets)->exists();

        return $hasCurrentSet && $hasCompletedSet && !$hasCurrentSetLogs;
    }

    private function navigateBackToPreviousSet(Game $game): void
    {
        DB::transaction(function () use ($game): void {
            $activeSet = GameSet::query()->where('game_id', $game->id)->whereNull('finished_at')->orderByDesc('id')->first();

            $latestCompletedSet = GameSet::query()->where('game_id', $game->id)->whereNotNull('finished_at')->orderByDesc('id')->first();

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

            GameSet::query()->create([
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

<div class="relative bg-background px-4 sm:px-6 py-6 w-svw h-svh">
    @if ($showStartOverlay)
        <div class="z-50 absolute inset-0 flex justify-center items-center bg-background/90 backdrop-blur-sm">
            <div class="bg-card shadow-xl p-6 sm:p-8 border border-border rounded-3xl w-full max-w-2xl">
                <p class="text-muted-foreground text-sm text-center uppercase tracking-[0.16em]">
                    Početak meča
                </p>

                <p class="mt-2 font-display font-semibold text-foreground text-4xl sm:text-5xl text-center">
                    {{ $playerOneName }}
                </p>
                <p class="mt-1 font-semibold text-muted-foreground text-lg text-center uppercase tracking-[0.12em]">
                    protiv
                </p>
                <p class="mt-1 font-display font-semibold text-foreground text-4xl sm:text-5xl text-center">
                    {{ $playerTwoName }}
                </p>

                <div class="flex flex-wrap justify-center items-center gap-2 mt-6">
                    <span
                        class="bg-background/80 px-4 py-2 border border-border/70 rounded-full font-semibold text-foreground text-sm">
                        {{ $roundName }}
                    </span>
                    <span
                        class="bg-background/80 px-4 py-2 border border-border/70 rounded-full font-semibold text-foreground text-sm">
                        {{ $groupName }}
                    </span>
                </div>

                <div class="flex justify-center mt-6">
                    <button type="button" wire:click="startMatch"
                        class="bg-primary shadow-lg px-10 py-6 border border-primary/30 rounded-3xl font-display font-semibold text-primary-foreground text-4xl transition hover:-translate-y-0.5 cursor-pointer">
                        Start
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showRestartConfirmation)
        <div class="z-[60] absolute inset-0 flex justify-center items-center bg-background/90 backdrop-blur-sm">
            <div class="bg-card shadow-xl p-6 border border-border rounded-3xl w-full max-w-sm">
                <p class="font-semibold text-foreground text-base text-center">
                    Restart this game?
                </p>
                <p class="mt-2 text-muted-foreground text-sm text-center">
                    This will clear all score history and start a fresh game clock.
                </p>

                <div class="flex justify-center items-center gap-3 mt-5">
                    <button type="button" wire:click="confirmRestartGame"
                        class="bg-red-500/10 hover:bg-red-500/15 px-4 py-2 border border-red-500/50 rounded-xl font-semibold text-red-600 text-sm uppercase tracking-[0.08em] transition cursor-pointer">
                        Restart
                    </button>
                    <button type="button" wire:click="cancelRestartGame"
                        class="bg-background px-4 py-2 border border-border hover:border-foreground/40 rounded-xl font-semibold text-foreground text-sm uppercase tracking-[0.08em] transition cursor-pointer">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showNextSetOverlay)
        <div class="z-55 absolute inset-0 flex justify-center items-center bg-background/90 backdrop-blur-sm">
            <div class="bg-card shadow-xl p-6 border border-border rounded-3xl w-full max-w-md">
                <p class="font-semibold text-foreground text-base text-center">
                    Set završen
                </p>

                <div class="flex justify-center items-center gap-3 mt-5">
                    <button type="button" wire:click="startNextSet"
                        class="bg-primary shadow-lg px-5 py-3 border border-primary/30 rounded-2xl font-display font-semibold text-primary-foreground text-base uppercase tracking-[0.08em] transition hover:-translate-y-0.5 cursor-pointer">
                        Sljedeći set
                    </button>
                    <button type="button" wire:click="undoLastLog"
                        class="bg-background px-5 py-3 border border-border hover:border-foreground/40 rounded-2xl font-semibold text-foreground text-base uppercase tracking-[0.08em] transition cursor-pointer">
                        Poništi
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showMatchDoneOverlay)
        <div class="z-55 absolute inset-0 flex justify-center items-center bg-background/90 backdrop-blur-sm"
            data-match-done-overlay="true" x-data="{ burst: {{ $matchDoneBurst }} }" x-init="$nextTick(() => { if (window.launchMatchDoneConfetti) { window.launchMatchDoneConfetti(); } })">

            <div class="relative bg-card shadow-xl p-6 sm:p-8 border border-border rounded-3xl w-full max-w-2xl">
                @if ($matchIsDraw)
                    <p class="font-display font-semibold text-foreground text-4xl sm:text-5xl text-center">
                        Remi
                    </p>
                    <div class="mx-auto mt-3 bg-border/70 w-full max-w-md h-px"></div>
                    <p class="mt-4 font-display font-semibold text-foreground text-lg sm:text-xl text-center">
                        {{ $playerOneName }}
                    </p>
                    <p class="mt-1 font-semibold text-muted-foreground text-lg text-center uppercase tracking-[0.12em]">
                        VS
                    </p>
                    <p class="mt-1 font-display font-semibold text-foreground text-lg sm:text-xl text-center">
                        {{ $playerTwoName }}
                    </p>
                @else
                    <p class="text-muted-foreground text-sm text-center uppercase tracking-[0.16em]">
                        Pobjednik
                    </p>
                    <p class="mt-1 font-display font-semibold text-foreground text-4xl sm:text-5xl text-center">
                        {{ $matchWinnerName }}
                    </p>

                    <p class="mt-5 text-muted-foreground text-sm text-center uppercase tracking-[0.16em]">
                        Luzer
                    </p>
                    <p class="mt-1 font-display font-semibold text-foreground text-2xl sm:text-3xl text-center">
                        {{ $matchLoserName }}
                    </p>
                @endif

                <div class="mt-6">
                    <p class="text-muted-foreground text-sm text-center uppercase tracking-[0.16em]">
                        Konačni rezultat
                    </p>
                    <p class="mt-1 font-display font-semibold text-foreground text-3xl sm:text-4xl text-center">
                        {{ $matchFinalResult }}
                    </p>
                </div>

                <div class="mt-6">
                    <div class="flex flex-wrap justify-center items-center gap-2 mt-3">
                        @foreach ($setPointBadges as $badge)
                            <span
                                class="bg-background/80 px-4 py-2 border border-border/70 rounded-full font-semibold text-foreground text-sm">
                                {{ $badge }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col items-center gap-3 mt-6">
                    <button type="button" wire:click="finishMatch"
                        class="bg-primary shadow-lg px-8 py-4 border border-primary/30 rounded-2xl w-full max-w-xs font-display font-semibold text-primary-foreground text-2xl uppercase tracking-[0.08em] transition hover:-translate-y-0.5 cursor-pointer">
                        Završi
                    </button>

                    <button type="button" wire:click="undoLastLog"
                        class="bg-background px-4 py-2 border border-border hover:border-foreground/40 rounded-xl font-semibold text-foreground text-sm uppercase tracking-[0.08em] transition cursor-pointer">
                        Poništi
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-[40%_20%_40%] w-full h-full">
        <section class="flex flex-col gap-3 sm:gap-4 pr-2 sm:pr-3 h-full min-h-0">
            <div
                class="bg-primary shadow-sm px-4 py-6 border border-primary/35 rounded-2xl w-full text-primary-foreground text-center">
                <p
                    class="font-display text-xl landscape:text-xl sm:text-3xl landscape:lg:text-4xl lg:text-4xl truncate whitespace-nowrap">
                    {{ $playerOneShortName }}</p>
            </div>

            <div wire:click="awardLeftPoint"
                class="relative flex flex-1 justify-center items-center bg-slate-900 shadow-lg px-4 py-5 border border-emerald-950/70 rounded-3xl min-h-0 overflow-hidden text-emerald-50 cursor-pointer">
                <p class="font-display font-bold text-[clamp(5.25rem,16vw,10.5rem)] leading-none">
                    <span class="select-none">
                        {{ $playerOneScore }}
                    </span>
                </p>

                @if ($servingPlayer === null || $servingPlayer === 'left')
                    <button type="button" wire:click.stop="selectServe('left')"
                        class="bottom-3 left-3 absolute bg-slate-950 hover:bg-slate-900 px-5 sm:px-4 py-3 sm:py-3 border border-slate-500/40 rounded-xl sm:max-h-none font-bold text-slate-100 text-3xl sm:text-3xl uppercase leading-none tracking-[0.08em] transition cursor-pointer">
                        {{ $this->serveButtonLabel('left') }}
                    </button>
                @endif
            </div>
        </section>

        <section class="flex flex-col gap-3 sm:gap-4 px-1 sm:px-2 h-full min-h-0">
            <div class="bg-card shadow-sm px-3 py-4 border border-border/70 rounded-2xl text-center">
                <p class="font-semibold text-muted-foreground text-xs uppercase tracking-[0.18em]">
                    {{ $roundName }}
                </p>
                <p class="mt-1 font-semibold text-foreground text-sm">
                    {{ $groupName }}
                    | Best of {{ $bestOf }}
                </p>
                <p class="mt-1 font-display text-foreground text-2xl sm:text-2xl text-nowrap">
                    {{ $playerOneSets }} - {{ $playerTwoSets }}
                </p>
            </div>

            <div class="flex-1 bg-card shadow-sm p-2 border border-border/70 rounded-2xl min-h-0 overflow-auto">
                <ul class="space-y-1.5">
                    @forelse ($historyScores as $index => $historyScore)
                        <li wire:key="history-score-{{ $index }}"
                            class="bg-background px-2 py-1.5 border border-border/60 rounded-lg font-semibold text-foreground text-sm sm:text-base text-center">
                            {{ $historyScore }}
                        </li>
                    @empty
                        <li
                            class="bg-background px-2 py-1.5 border border-border/60 rounded-lg font-semibold text-muted-foreground text-sm sm:text-base text-center">
                            Nema povijesti bodova.
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="gap-2 grid grid-cols-2">
                <button type="button" wire:click="undoLastLog"
                    class="flex justify-center items-center bg-card shadow-sm px-3 py-3 border border-border hover:border-foreground/40 rounded-2xl font-semibold text-foreground text-sm uppercase tracking-[0.08em] transition hover:-translate-y-0.5"
                    aria-label="Undo">
                    <x-heroicon-o-arrow-uturn-left class="landscape:lg:hidden sm:hidden landscape:inline w-5 h-5"
                        aria-hidden="true" />
                    <span class="hidden landscape:hidden landscape:lg:inline sm:inline">Undo</span>
                </button>

                <button type="button" wire:click="requestRestartGame"
                    class="flex justify-center items-center bg-red-500/10 hover:bg-red-500/15 shadow-sm px-3 py-3 border border-red-500/50 rounded-2xl font-semibold text-red-600 text-sm uppercase tracking-[0.08em] transition hover:-translate-y-0.5"
                    aria-label="Restart">
                    <x-heroicon-o-arrow-path class="landscape:lg:hidden sm:hidden landscape:inline w-5 h-5"
                        aria-hidden="true" />
                    <span class="hidden landscape:hidden landscape:lg:inline sm:inline">Restart</span>
                </button>
            </div>
        </section>

        <section class="flex flex-col gap-3 sm:gap-4 pl-2 sm:pl-3 h-full min-h-0">
            <div
                class="bg-primary shadow-sm px-4 py-6 border border-primary/35 rounded-2xl w-full text-primary-foreground text-center">
                <p
                    class="font-display text-xl landscape:text-xl sm:text-3xl landscape:lg:text-4xl lg:text-4xl truncate whitespace-nowrap">
                    {{ $playerTwoShortName }}</p>
            </div>

            <div wire:click="awardRightPoint"
                class="relative flex flex-1 justify-center items-center bg-slate-900 shadow-lg px-4 py-5 border border-emerald-950/70 rounded-3xl min-h-0 overflow-hidden text-emerald-50 cursor-pointer">
                <p class="font-display font-bold text-[clamp(5.25rem,16vw,10.5rem)] leading-none">
                    <span class="select-none">
                        {{ $playerTwoScore }}
                    </span>
                </p>

                @if ($servingPlayer === null || $servingPlayer === 'right')
                    <button type="button" wire:click.stop="selectServe('right')"
                        class="right-3 bottom-3 absolute bg-slate-950 hover:bg-slate-900 px-5 sm:px-4 py-3 sm:py-3 border border-slate-500/40 rounded-xl font-bold text-slate-100 text-3xl sm:text-3xl uppercase leading-none tracking-[0.08em] transition cursor-pointer">
                        {{ $this->serveButtonLabel('right') }}
                    </button>
                @endif
            </div>
        </section>
    </div>
</div>
