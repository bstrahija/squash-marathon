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

        if (! $event) {
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
    <div class="tv-group-leaderboard-heading flex items-center justify-between text-muted-foreground">
        <span class="font-semibold uppercase tracking-[0.16em]">{{ $groupName }}</span>
        <span class="font-semibold uppercase tracking-[0.16em]">{{ $roundName }}</span>
    </div>

    <div class="min-h-0 flex-1 overflow-hidden bg-background/40">
        <div class="h-full overflow-auto">
            <table class="tv-leaderboard-table w-full text-left leading-tight">
                <thead
                    class="tv-leaderboard-head sticky top-0 bg-background/90 uppercase tracking-widest text-muted-foreground backdrop-blur-sm">
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
                        <tr class="odd:bg-background/35 even:bg-transparent"
                            wire:key="tv-group-leaderboard-{{ $this->groupNumber }}-{{ $row['id'] }}">
                            <td class="tv-leaderboard-cell font-medium text-foreground">
                                @if ($row['profile_url'])
                                    <a href="{{ $row['profile_url'] }}" class="rounded-sm transition hover:underline">
                                        {{ $row['name'] }}
                                    </a>
                                @else
                                    <span>{{ $row['name'] }}</span>
                                @endif
                            </td>
                            <td class="tv-leaderboard-cell font-semibold text-foreground">{{ $row['points'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['wins'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['draws'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['losses'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tv-leaderboard-cell text-center text-muted-foreground" colspan="5">
                                Jos nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
