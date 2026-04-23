<?php

use App\Models\Event;
use App\Models\GameSchedule;
use App\Models\Round;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $linkToScoring = false;

    public function mount(bool $linkToScoring = false): void
    {
        $this->linkToScoring = $linkToScoring;
    }

    #[Computed]
    public function groups(): Collection
    {
        $event = Event::current();

        if (! $event) {
            return collect();
        }

        $activeRound = Round::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->with('groups')
            ->orderByDesc('number')
            ->first();

        if (! $activeRound) {
            return collect();
        }

        return $activeRound->groups
            ->whereIn('number', [1, 2])
            ->sortBy('number')
            ->values();
    }

    #[Computed]
    public function schedulesByGroup(): Collection
    {
        $groups = $this->groups;

        if ($groups->isEmpty()) {
            return collect();
        }

        $groupIds = $groups
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $roundIds = $groups
            ->pluck('round_id')
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->all();

        return GameSchedule::query()
            ->with([
                'playerOne',
                'playerTwo',
                'game.sets',
                'game.gameLogs' => fn ($query) => $query->orderBy('sequence'),
            ])
            ->whereIn('round_id', $roundIds)
            ->whereIn('group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->reject(fn (GameSchedule $schedule): bool => (bool) $schedule->game?->isFinished())
            ->groupBy('group_id');
    }
};
?>

<div wire:poll.10s>
    @if ($this->groups->isEmpty())
        <div class="rounded-2xl border border-border/70 bg-card/50 p-4 text-sm text-muted-foreground">
            Trenutno nema aktivne runde s grupama za prikaz rasporeda.
        </div>
    @else
        <x-rounds.schedule-overview :groups="$this->groups" :schedules-by-group="$this->schedulesByGroup" :link-to-scoring="$linkToScoring" />
    @endif
</div>
