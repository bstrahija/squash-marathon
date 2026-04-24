<?php

use App\Models\Event;
use App\Models\GameSchedule;
use App\Models\Round;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $groupNumber = 1;

    public function mount(int $groupNumber = 1): void
    {
        $this->groupNumber = $groupNumber;
    }

    #[Computed]
    public function schedule(): array
    {
        $event = Event::current();

        if (!$event) {
            return [
                'round' => null,
                'group' => null,
                'rows' => [],
            ];
        }

        $round = Round::query()->where('event_id', $event->id)->where('is_active', true)->orderByDesc('number')->with('groups')->first();

        if (!$round) {
            $round = Round::query()->where('event_id', $event->id)->orderByDesc('number')->with('groups')->first();
        }

        if (!$round) {
            return [
                'round' => null,
                'group' => null,
                'rows' => [],
            ];
        }

        $group = $round->groups->firstWhere('number', $this->groupNumber);

        if (!$group) {
            return [
                'round' => [
                    'id' => $round->id,
                    'name' => $round->name,
                    'number' => $round->number,
                ],
                'group' => null,
                'rows' => [],
            ];
        }

        $schedules = GameSchedule::query()
            ->with(['playerOne', 'playerTwo', 'game.sets', 'game.gameLogs' => fn($query) => $query->orderBy('sequence')])
            ->where('round_id', $round->id)
            ->where('group_id', $group->id)
            ->orderByRaw('starts_at is null, starts_at asc')
            ->orderBy('id')
            ->get();

        $liveSchedule = $schedules->first(fn(GameSchedule $schedule): bool => (bool) $schedule->game?->isLive());

        $recentSchedule = $schedules->filter(fn(GameSchedule $schedule): bool => (bool) $schedule->game?->isFinished())->sortByDesc(fn(GameSchedule $schedule): mixed => $schedule->game?->finished_at ?? ($schedule->game?->updated_at ?? ($schedule->starts_at ?? $schedule->id)))->first();

        $primarySchedule = $liveSchedule ?? $recentSchedule;

        $upcomingSchedules = $schedules->reject(fn(GameSchedule $schedule): bool => (bool) $schedule->game?->isFinished());

        if ($primarySchedule) {
            $upcomingSchedules = $upcomingSchedules->reject(fn(GameSchedule $schedule): bool => $schedule->id === $primarySchedule->id);
        }

        $primaryId = $primarySchedule?->id;

        $rows = collect([$primarySchedule])
            ->filter()
            ->merge($upcomingSchedules)
            ->map(function (GameSchedule $schedule) use ($primaryId): array {
                $latestSet = $schedule->game?->sets?->sortBy('created_at')->last();
                $latestLog = $schedule->game?->gameLogs?->sortBy('sequence')->last();

                $playerOnePoints = (int) ($latestLog?->player_one_score ?? ($latestSet?->player_one_score ?? 0));
                $playerTwoPoints = (int) ($latestLog?->player_two_score ?? ($latestSet?->player_two_score ?? 0));
                $playerOneSets = (int) ($schedule->game?->player_one_sets ?? 0);
                $playerTwoSets = (int) ($schedule->game?->player_two_sets ?? 0);

                return [
                    'id' => $schedule->id,
                    'starts_at' => $schedule->starts_at,
                    'player_one' => $schedule->playerOne?->short_name ?? ($schedule->playerOne?->full_name ?? '—'),
                    'player_two' => $schedule->playerTwo?->short_name ?? ($schedule->playerTwo?->full_name ?? '—'),
                    'has_game' => (bool) $schedule->game,
                    'is_live' => (bool) $schedule->game?->isLive(),
                    'is_primary' => $schedule->id === $primaryId,
                    'score' => $schedule->game ? sprintf('(%d) %d:%d (%d)', $playerOneSets, $playerOnePoints, $playerTwoPoints, $playerTwoSets) : null,
                ];
            })
            ->values()
            ->all();

        return [
            'round' => [
                'id' => $round->id,
                'name' => $round->name,
                'number' => $round->number,
            ],
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'number' => $group->number,
            ],
            'rows' => $rows,
        ];
    }
};
?>

@php
    $roundName = $this->schedule['round']['name'] ?? 'Runda -';
    $groupName = $this->schedule['group']['name'] ?? "Grupa {$this->groupNumber}";
@endphp

<div class="tv-group-schedule flex flex-col h-full min-h-0" wire:poll.10s>
    <div class="text-muted-foreground tv-schedule-kicker">Raspored</div>
    <div class="flex justify-between items-center text-muted-foreground tv-schedule-heading">
        <span class="font-semibold uppercase tracking-[0.16em]">{{ $groupName }}</span>
        <span class="font-semibold uppercase tracking-[0.16em]">{{ $roundName }}</span>
    </div>

    <div class="flex-1 bg-background/40 min-h-0 overflow-hidden">
        <div class="h-full overflow-auto">
            @if (count($this->schedule['rows']) === 0)
                <div class="px-4 py-6 text-muted-foreground text-sm">
                    Raspored jos nije kreiran.
                </div>
            @else
                <div class="divide-y divide-border/60">
                    @foreach ($this->schedule['rows'] as $row)
                        <div class="tv-schedule-row {{ $row['is_primary'] ? 'tv-schedule-row-primary' : '' }} {{ $row['is_live'] ? 'tv-schedule-row-live' : '' }}"
                            wire:key="tv-schedule-{{ $this->groupNumber }}-{{ $row['id'] }}">
                            <div class="font-semibold text-foreground tv-schedule-player tv-schedule-player-one">
                                {{ $row['player_one'] }}
                            </div>
                            <div class="text-muted-foreground tv-schedule-score">
                                {{ $row['score'] ?? '—' }}
                            </div>
                            <div class="font-semibold text-foreground tv-schedule-player tv-schedule-player-two">
                                {{ $row['player_two'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
