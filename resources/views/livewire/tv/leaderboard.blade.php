<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function leaderboard(): array
    {
        $event = Event::current();

        if (!$event) {
            return [];
        }

        return $event
            ->leaderboardRows()
            ->map(
                fn(array $row): array => [
                    'id' => $row['id'],
                    'name' => $row['short_name'],
                    'profile_url' => $row['profile_url'],
                    'wins' => $row['wins'],
                    'draws' => $row['draws'],
                    'losses' => $row['losses'],
                    'points' => $row['points'],
                ],
            )
            ->all();
    }

    #[Computed]
    public function density(): string
    {
        $rows = count($this->leaderboard);

        if ($rows <= 6) {
            return 'comfortable';
        }

        if ($rows <= 10) {
            return 'balanced';
        }

        return 'compact';
    }
};
?>

<div class="tv-leaderboard tv-density-{{ $this->density }} flex h-full min-h-0 flex-col" wire:poll.keep-alive.20s>
    <div class="bg-background/40 min-h-0 flex-1 overflow-hidden">
        <div class="h-full overflow-auto">
            <table class="tv-leaderboard-table w-full text-left leading-tight">
                <thead
                       class="tv-leaderboard-head bg-background/90 text-muted-foreground sticky top-0 uppercase tracking-widest backdrop-blur-sm">
                    <tr>
                        <th class="tv-leaderboard-cell">Igrač</th>
                        <th class="tv-leaderboard-cell">Bod</th>
                        <th class="tv-leaderboard-cell">W</th>
                        <th class="tv-leaderboard-cell">D</th>
                        <th class="tv-leaderboard-cell">L</th>
                    </tr>
                </thead>
                <tbody class="divide-border/70 divide-y">
                    @forelse ($this->leaderboard as $row)
                        <tr class="odd:bg-background/35 even:bg-transparent"
                            wire:key="tv-leaderboard-{{ $row['id'] }}">
                            <td class="tv-leaderboard-cell text-foreground font-medium">
                                <a href="{{ $row['profile_url'] }}" class="rounded-sm transition hover:underline">
                                    {{ $row['name'] }}
                                </a>
                            </td>
                            <td class="tv-leaderboard-cell text-foreground font-semibold">{{ $row['points'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['wins'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['draws'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['losses'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tv-leaderboard-cell text-muted-foreground text-center" colspan="5">
                                Još nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
