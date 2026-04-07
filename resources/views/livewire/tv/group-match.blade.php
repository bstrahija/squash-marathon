<?php

use App\Models\Event;
use App\Models\Game;
use App\Models\Set;
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
        $event = Event::query()->latest('id')->first();

        if (!$event) {
            return null;
        }

        $games = Game::query()
            ->with(['sets', 'playerOne', 'playerTwo', 'group', 'gameLogs' => fn($query) => $query->orderBy('sequence')])
            ->where('event_id', $event->id)
            ->whereHas('group', fn($query) => $query->where('number', $this->groupNumber))
            ->get();

        if ($games->isEmpty()) {
            return null;
        }

        $liveGame = $games->filter(fn(Game $game): bool => $this->isLiveGame($game))->sortByDesc(fn(Game $game): int => $game->started_at?->timestamp ?? ($game->created_at?->timestamp ?? 0))->first();

        if ($liveGame) {
            return $this->mapGame($liveGame);
        }

        $waitingGame = $games->filter(fn(Game $game): bool => $this->isWaitingGame($game))->sortByDesc(fn(Game $game): int => $game->created_at?->timestamp ?? 0)->first();

        if ($waitingGame) {
            return $this->mapGame($waitingGame);
        }

        $finishedGame = $games->filter(fn(Game $game): bool => $this->isFinishedGame($game))->sortByDesc(fn(Game $game): int => $game->finished_at?->timestamp ?? ($game->created_at?->timestamp ?? 0))->first();

        if ($finishedGame) {
            return $this->mapGame($finishedGame);
        }

        $latestGame = $games->sortByDesc(fn(Game $game): int => $game->created_at?->timestamp ?? 0)->first();

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

        $result = Game::determineMatchResultFromSetScores(
            $orderedSets
                ->map(
                    fn(Set $set): array => [
                        'player_one_score' => $set->player_one_score,
                        'player_two_score' => $set->player_two_score,
                    ],
                )
                ->all(),
            $game->best_of,
            $game->player_one_id,
            $game->player_two_id,
        );

        $isLive = $this->isLiveGame($game);
        $isFinished = $this->isFinishedGame($game);
        $isDraw = (bool) ($result['is_complete'] && $result['is_draw']);
        $winnerId = $result['winner_id'] ?? null;

        return [
            'id' => $game->id,
            'group_name' => $game->group?->name ?? "Group {$this->groupNumber}",
            'player_one' => $this->formatPlayerDisplayName($game->playerOne?->full_name),
            'player_two' => $this->formatPlayerDisplayName($game->playerTwo?->full_name),
            'player_one_full' => $game->playerOne?->full_name ?? 'Igrac 1',
            'player_two_full' => $game->playerTwo?->full_name ?? 'Igrac 2',
            'player_one_current' => (int) ($latestLog?->player_one_score ?? ($latestSet?->player_one_score ?? 0)),
            'player_two_current' => (int) ($latestLog?->player_two_score ?? ($latestSet?->player_two_score ?? 0)),
            'sets_one' => $result['player_one_wins'],
            'sets_two' => $result['player_two_wins'],
            'timeline' => $orderedSets
                ->filter(fn(Set $set): bool => filled($set->player_one_score) && filled($set->player_two_score))
                ->map(
                    fn(Set $set): array => [
                        'id' => $set->id,
                        'score' => "{$set->player_one_score}:{$set->player_two_score}",
                    ],
                )
                ->all(),
            'status' => $isLive ? 'UŽIVO' : ($isFinished ? 'ZAVRŠENO' : 'NA ČEKANJU'),
            'status_class' => $isLive ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : ($isFinished ? 'bg-sky-500/15 text-sky-600 dark:text-sky-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'),
            'status_effect_class' => $isLive ? 'tv-group-status-live' : '',
            'player_one_class' => $this->playerClass($game->player_one_id, $winnerId, $isDraw),
            'player_two_class' => $this->playerClass($game->player_two_id, $winnerId, $isDraw),
        ];
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
        $result = Game::determineMatchResultFromSetScores(
            $game->sets
                ->map(
                    fn(Set $set): array => [
                        'player_one_score' => $set->player_one_score,
                        'player_two_score' => $set->player_two_score,
                    ],
                )
                ->all(),
            $game->best_of,
            $game->player_one_id,
            $game->player_two_id,
        );

        return (bool) ($game->finished_at || $result['is_complete']);
    }

    private function isWaitingGame(Game $game): bool
    {
        return !$game->started_at && !$game->finished_at;
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

    private function formatPlayerDisplayName(?string $fullName): string
    {
        $fullName = trim((string) $fullName);

        if ($fullName === '') {
            return 'Igrac';
        }

        $parts = preg_split('/\s+/u', $fullName) ?: [];
        $firstName = $parts[0] ?? '';

        if ($firstName === '') {
            return $fullName;
        }

        $firstInitial = mb_substr($firstName, 0, 1);
        $lastName = trim(implode(' ', array_slice($parts, 1)));

        if ($lastName === '') {
            return sprintf('%s.', $firstInitial);
        }

        return sprintf('%s. %s', $firstInitial, $lastName);
    }
};
?>

@php
    $match = $this->match;
@endphp

<div class="tv-group-match flex h-full min-h-0 flex-col" wire:poll.3s>
    @if ($match)
        <div class="tv-group-grid grid h-full min-h-0 flex-1 items-stretch overflow-hidden bg-background/35">
            <div class="tv-group-player-column flex h-full min-h-0 flex-col items-center justify-center text-center">
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
                <span
                    class="tv-group-status rounded-full px-3 py-1 font-semibold tracking-wide {{ $match['status_class'] }} {{ $match['status_effect_class'] }}">
                    {{ $match['status'] }}
                </span>
                <div class="tv-group-set-score font-display font-semibold leading-none tracking-tight text-foreground">
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
                        <span class="font-semibold text-muted-foreground">Nema upisanih poena</span>
                    @endforelse
                </div>
            </div>

            <div class="tv-group-player-column flex h-full min-h-0 flex-col items-center justify-center text-center">
                <p title="{{ $match['player_two_full'] }}"
                    class="tv-group-player-name truncate whitespace-nowrap font-semibold leading-tight {{ $match['player_two_class'] }}">
                    {{ $match['player_two'] }}</p>
                <p
                    class="tv-group-point self-center text-center font-display font-normal leading-none text-foreground/80">
                    {{ $match['sets_two'] }}</p>
            </div>
        </div>
    @else
        <div
            class="tv-group-empty flex min-h-0 flex-1 items-center justify-center bg-background/35 px-6 text-center text-muted-foreground">
            Nema meceva za ovu grupu.
        </div>
    @endif
</div>
