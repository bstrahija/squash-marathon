<?php

use App\Actions\GetEventPlayerStatsAction;
use App\Actions\GetGroupStandingsAction;
use App\Actions\SortLeaderboardRowsAction;
use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public ?int $groupNumber = null;

    public string $sortBy = 'points';

    public string $sortDirection = 'desc';

    public function mount(?int $groupNumber = null): void
    {
        $this->groupNumber = $groupNumber;
    }

    public function sortByColumn(string $column): void
    {
        if (!in_array($column, ['points', 'matches', 'wins', 'draws', 'losses', 'sets_won', 'points_scored', 'duration_seconds'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';

            return;
        }

        $this->sortBy = $column;
        $this->sortDirection = 'desc';
    }

    #[Computed]
    public function leaderboardRows(): array
    {
        $event = Event::current();

        if (!$event) {
            return [];
        }

        if ($this->groupNumber !== null) {
            return app(GetGroupStandingsAction::class)->execute($event, $this->groupNumber)['rows'];
        }

        return app(GetEventPlayerStatsAction::class)
            ->execute($event)
            ->map(
                fn(array $row): array => [
                    'id' => $row['player']->id,
                    'name' => $row['player']->full_name,
                    'short_name' => $row['player']->short_name,
                    'profile_url' => route('players.show', ['user' => $row['player']->id]),
                    'matches' => $row['games'],
                    'wins' => $row['wins'],
                    'draws' => $row['draws'],
                    'losses' => $row['losses'],
                    'sets_won' => $row['sets_won'],
                    'sets_lost' => $row['sets_lost'],
                    'points_scored' => $row['points_scored'],
                    'points_allowed' => $row['points_allowed'],
                    'duration_seconds' => $row['duration_seconds'],
                    'points' => $row['wins'] * 3 + $row['draws'] * 2 + $row['losses'],
                ],
            )
            ->values()
            ->all();
    }

    #[Computed]
    public function leaderboard(): array
    {
        return app(SortLeaderboardRowsAction::class)->execute($this->leaderboardRows, $this->sortBy, $this->sortDirection);
    }

    public function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
};
?>

<div class="" wire:poll.20s>
    <div class="overflow-hidden rounded-2xl border border-border bg-card">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[740px] table-fixed bg-card text-left text-sm">
                <thead class="bg-card text-xs uppercase tracking-widest text-muted-foreground">
                    <tr>
                        <th class="sticky left-0 z-20 w-10 bg-card px-2 py-3 text-right md:static md:z-auto">
                            #</th>
                        <th
                            class="sticky left-10 z-20 w-[6.5rem] bg-card px-3 py-3 font-semibold md:static md:z-auto sm:w-32">
                            Igrač</th>
                        <th class="w-14 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('points')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                B
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'points')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-14 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('matches')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                M
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'matches')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-14 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('wins')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                W
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'wins')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-14 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('draws')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                D
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'draws')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-14 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('losses')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                L
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'losses')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-18 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('sets_won')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                S
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'sets_won')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-20 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('points_scored')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap">
                                P
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'points_scored')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                        <th class="w-20 bg-card px-2 py-3 z-10 relative">
                            <button type="button" wire:click="sortByColumn('duration_seconds')"
                                class="inline-flex items-center gap-1 font-semibold cursor-pointer whitespace-nowrap"
                                aria-label="Trajanje">
                                <x-heroicon-o-clock class="w-4 h-4" />
                                <span class="inline-flex h-3.5 w-3.5 items-center justify-center">
                                    @if ($sortBy === 'duration_seconds')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="w-3.5 h-3.5" />
                                        @else
                                            <x-heroicon-o-chevron-down class="w-3.5 h-3.5" />
                                        @endif
                                    @endif
                                </span>
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border relative z-10">
                    @forelse ($this->leaderboard as $row)
                        <tr class="bg-card transition hover:bg-muted"
                            wire:key="stats-leaderboard-{{ $groupNumber ?? 'main' }}-{{ $row['id'] }}">
                            <td
                                class="sticky left-0 z-20 w-10 bg-card px-2 py-3 text-right font-semibold text-muted-foreground md:static md:z-auto">
                                {{ $loop->iteration }}</td>
                            <td
                                class="sticky left-10 z-20 w-[6.5rem] bg-card px-3 py-3 font-semibold text-foreground md:static md:z-auto sm:w-32">
                                @if ($row['profile_url'])
                                    <a href="{{ $row['profile_url'] }}"
                                        class="inline-block max-w-full rounded-md transition hover:text-emerald-600 hover:underline dark:hover:text-emerald-400">
                                        <span
                                            class="block whitespace-nowrap text-xs sm:hidden">{{ $row['short_name'] ?? $row['name'] }}</span>
                                        <span class="hidden sm:inline">{{ $row['name'] }}</span>
                                    </a>
                                @else
                                    <span
                                        class="block whitespace-nowrap text-xs sm:hidden">{{ $row['short_name'] ?? $row['name'] }}</span>
                                    <span class="hidden sm:inline">{{ $row['name'] }}</span>
                                @endif
                            </td>
                            <td class="w-14 bg-card px-2 py-3 font-semibold text-foreground z-10 relative">
                                {{ $row['points'] }}</td>
                            <td class="w-14 bg-card px-2 py-3 text-muted-foreground z-10 relative">{{ $row['matches'] }}
                            </td>
                            <td class="w-14 bg-card px-2 py-3 text-muted-foreground z-10 relative">{{ $row['wins'] }}
                            </td>
                            <td class="w-14 bg-card px-2 py-3 text-muted-foreground z-10 relative">{{ $row['draws'] }}
                            </td>
                            <td class="w-14 bg-card px-2 py-3 text-muted-foreground z-10 relative">{{ $row['losses'] }}
                            </td>
                            <td class="w-18 bg-card px-2 py-3 text-muted-foreground z-10 relative">
                                {{ $row['sets_won'] }}/{{ $row['sets_lost'] }}</td>
                            <td class="w-20 bg-card px-2 py-3 text-muted-foreground z-10 relative">
                                {{ $row['points_scored'] }}/{{ $row['points_allowed'] }}</td>
                            <td class="w-20 bg-card px-2 py-3 text-muted-foreground z-10 relative">
                                {{ $this->formatDuration((int) $row['duration_seconds']) }}</td>
                        </tr>
                    @empty
                        <tr class="bg-card">
                            <td class="px-4 py-6 text-center text-sm text-muted-foreground" colspan="10">
                                Još nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
