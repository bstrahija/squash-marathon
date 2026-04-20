<?php

use App\Actions\GetEventPlayerStatsAction;
use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function leaderboard(): array
    {
        $event = Event::current();

        if (! $event) {
            return [];
        }

        return app(GetEventPlayerStatsAction::class)
            ->execute($event)
            ->map(fn (array $row): array => [
                'id'           => $row['player']->id,
                'name'         => $row['player']->short_name,
                'profile_url'  => route('players.show', ['user' => $row['player']->id]),
                'wins'         => $row['wins'],
                'draws'        => $row['draws'],
                'losses'       => $row['losses'],
                'points'       => $row['wins'] * 3 + $row['draws'] * 2 + $row['losses'],
                'last_game_at' => $row['last_game_at'],
            ])
            ->sort(function (array $left, array $right): int {
                if ($left['points'] !== $right['points']) {
                    return $right['points'] <=> $left['points'];
                }

                if ($left['wins'] !== $right['wins']) {
                    return $right['wins'] <=> $left['wins'];
                }

                $leftTime  = $left['last_game_at']?->timestamp ?? 0;
                $rightTime = $right['last_game_at']?->timestamp ?? 0;

                return $rightTime <=> $leftTime;
            })
            ->values()
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
    <div class="min-h-0 flex-1 overflow-hidden bg-background/40">
        <div class="h-full overflow-auto">
            <table class="tv-leaderboard-table w-full text-left leading-tight">
                <thead
                    class="tv-leaderboard-head sticky top-0 bg-background/90 uppercase tracking-widest text-muted-foreground backdrop-blur-sm">
                    <tr>
                        <th class="tv-leaderboard-cell">Igrač</th>
                        <th class="tv-leaderboard-cell">Bod</th>
                        <th class="tv-leaderboard-cell">W</th>
                        <th class="tv-leaderboard-cell">D</th>
                        <th class="tv-leaderboard-cell">L</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/70">
                    @forelse ($this->leaderboard as $row)
                        <tr class="odd:bg-background/35 even:bg-transparent"
                            wire:key="tv-leaderboard-{{ $row['id'] }}">
                            <td class="tv-leaderboard-cell font-medium text-foreground">
                                <a href="{{ $row['profile_url'] }}" class="rounded-sm transition hover:underline">
                                    {{ $row['name'] }}
                                </a>
                            </td>
                            <td class="tv-leaderboard-cell font-semibold text-foreground">{{ $row['points'] }}
                            </td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['wins'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['draws'] }}</td>
                            <td class="tv-leaderboard-cell text-muted-foreground">{{ $row['losses'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="tv-leaderboard-cell text-center text-muted-foreground" colspan="5">
                                Još nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
