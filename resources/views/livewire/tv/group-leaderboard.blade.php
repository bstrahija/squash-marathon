<?php

use App\Actions\GetGroupStandingsAction;
use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $groupNumber = 1;

    public function mount(int $groupNumber = 1): void
    {
        $this->groupNumber = $groupNumber;
    }

    #[Computed]
    public function standings(): array
    {
        $event = Event::current();

        if (!$event) {
            return [
                'round' => null,
                'group' => null,
                'rows' => [],
            ];
        }

        return app(GetGroupStandingsAction::class)->execute($event, $this->groupNumber);
    }

    #[Computed]
    public function density(): string
    {
        $rows = count($this->standings['rows']);

        if ($rows <= 5) {
            return 'comfortable';
        }

        if ($rows <= 8) {
            return 'balanced';
        }

        return 'compact';
    }
};
?>

@php
    $roundName = $this->standings['round']['name'] ?? 'Runda -';
    $groupName = $this->standings['group']['name'] ?? "Grupa {$this->groupNumber}";
@endphp

<div class="tv-group-leaderboard tv-leaderboard tv-density-{{ $this->density }} flex h-full min-h-0 flex-col"
    wire:poll.keep-alive.20s>
    <div class="tv-group-leaderboard-heading flex justify-between items-center text-muted-foreground">
        <span class="font-semibold uppercase tracking-[0.16em] pl-3">{{ $groupName }}</span>
        <span class="font-semibold uppercase tracking-[0.16em] pr-3">{{ $roundName }}</span>
    </div>

    <div class="flex-1 bg-background/40 min-h-0 overflow-hidden">
        <div class="h-full overflow-auto">
            <table class="tv-leaderboard-table w-full text-left leading-tight">
                <thead
                    class="top-0 sticky bg-background/90 backdrop-blur-sm text-muted-foreground uppercase tracking-widest tv-leaderboard-head">
                    <tr>
                        <th class="tv-leaderboard-cell">Igrac</th>
                        <th class="tv-leaderboard-cell">Bod</th>
                        <th class="tv-leaderboard-cell">W</th>
                        <th class="tv-leaderboard-cell">D</th>
                        <th class="tv-leaderboard-cell">L</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/70">
                    @forelse ($this->standings['rows'] as $row)
                        <tr class="even:bg-transparent odd:bg-background/35"
                            wire:key="tv-group-leaderboard-{{ $this->groupNumber }}-{{ $row['id'] }}">
                            <td class="font-medium text-foreground tv-leaderboard-cell">
                                @if ($row['profile_url'])
                                    <a href="{{ $row['profile_url'] }}" class="rounded-sm hover:underline transition">
                                        {{ $row['name'] }}
                                    </a>
                                @else
                                    <span>{{ $row['name'] }}</span>
                                @endif
                            </td>
                            <td class="font-semibold text-foreground tv-leaderboard-cell">{{ $row['points'] }}</td>
                            <td class="text-muted-foreground tv-leaderboard-cell">{{ $row['wins'] }}</td>
                            <td class="text-muted-foreground tv-leaderboard-cell">{{ $row['draws'] }}</td>
                            <td class="text-muted-foreground tv-leaderboard-cell">{{ $row['losses'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted-foreground text-center tv-leaderboard-cell" colspan="5">
                                Jos nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
