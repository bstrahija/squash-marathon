<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function leaderboard(): array
    {
        $event = Event::query()->latest('start_at')->first();

        if (!$event) {
            return [];
        }

        $players = $event->users()->get();

        if ($players->isEmpty()) {
            $players = User::role(RoleName::Player->value)->get();
        }

        if ($players->isEmpty()) {
            $players = User::query()->get();
        }

        $games = Game::query()
            ->with(['sets'])
            ->where('event_id', $event->id)
            ->get();

        $stats = $players->mapWithKeys(function (User $user): array {
            return [
                $user->id => [
                    'player' => $user,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'games' => 0,
                    'last_game_at' => null,
                ],
            ];
        });

        foreach ($games as $game) {
            $result = Game::determineMatchResultFromSetScores(
                $game->sets
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

            if (!$result['is_complete']) {
                continue;
            }

            foreach ([$game->player_one_id, $game->player_two_id] as $playerId) {
                if (!$stats->has($playerId)) {
                    continue;
                }

                $row = $stats->get($playerId);
                $row['games'] += 1;

                if ($result['is_draw']) {
                    $row['draws'] += 1;
                } elseif ($playerId === $result['winner_id']) {
                    $row['wins'] += 1;
                } else {
                    $row['losses'] += 1;
                }

                if (!$row['last_game_at'] || $row['last_game_at']->lt($game->created_at)) {
                    $row['last_game_at'] = $game->created_at;
                }

                $stats->put($playerId, $row);
            }
        }

        return $stats
            ->values()
            ->map(
                fn(array $row): array => [
                    'id' => $row['player']->id,
                    'name' => $row['player']->full_name,
                    'wins' => $row['wins'],
                    'draws' => $row['draws'],
                    'losses' => $row['losses'],
                    'points' => $row['wins'] * 3 + $row['draws'] * 2 + $row['losses'],
                    'last_game_at' => $row['last_game_at'],
                ],
            )
            ->sort(function (array $left, array $right): int {
                if ($left['points'] !== $right['points']) {
                    return $right['points'] <=> $left['points'];
                }

                if ($left['wins'] !== $right['wins']) {
                    return $right['wins'] <=> $left['wins'];
                }

                $leftTime = $left['last_game_at']?->timestamp ?? 0;
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

@php
    $typography = match ($this->density) {
        'comfortable' => [
            'title' => 'text-[clamp(1.35rem,2.2vw,2.5rem)]',
            'meta' => 'text-[clamp(0.9rem,1.2vw,1.2rem)]',
            'table' => 'text-[clamp(1.15rem,1.7vw,1.7rem)]',
            'head' => 'text-[clamp(0.72rem,1vw,1rem)]',
            'cell' => 'px-5 py-4',
        ],
        'balanced' => [
            'title' => 'text-[clamp(1.12rem,1.6vw,1.95rem)]',
            'meta' => 'text-[clamp(0.76rem,0.95vw,1rem)]',
            'table' => 'text-[clamp(0.96rem,1.25vw,1.22rem)]',
            'head' => 'text-[clamp(0.64rem,0.82vw,0.84rem)]',
            'cell' => 'px-4 py-3',
        ],
        default => [
            'title' => 'text-[clamp(0.96rem,1.2vw,1.34rem)]',
            'meta' => 'text-[clamp(0.64rem,0.78vw,0.84rem)]',
            'table' => 'text-[clamp(0.78rem,0.95vw,0.98rem)]',
            'head' => 'text-[clamp(0.55rem,0.64vw,0.72rem)]',
            'cell' => 'px-3 py-2.5',
        ],
    };
@endphp

<div class="flex h-full min-h-0 flex-col p-[clamp(0.85rem,1.1vw,1.3rem)]" wire:poll.3s>
    <div class="mb-3 flex items-end justify-between gap-3">
        <h2 class="font-display font-semibold text-foreground {{ $typography['title'] }}">Leaderboard</h2>
        <span class="text-muted-foreground {{ $typography['meta'] }}">3 / 2 / 1</span>
    </div>

    <div class="min-h-0 flex-1 overflow-hidden bg-background/40">
        <div class="h-full overflow-auto">
            <table class="w-full text-left leading-tight {{ $typography['table'] }}">
                <thead
                    class="sticky top-0 bg-background/90 uppercase tracking-widest text-muted-foreground backdrop-blur-sm {{ $typography['head'] }}">
                    <tr>
                        <th class="{{ $typography['cell'] }}">Igrač</th>
                        <th class="{{ $typography['cell'] }}">W</th>
                        <th class="{{ $typography['cell'] }}">D</th>
                        <th class="{{ $typography['cell'] }}">L</th>
                        <th class="{{ $typography['cell'] }}">Bod</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/70">
                    @forelse ($this->leaderboard as $row)
                        <tr class="odd:bg-background/35 even:bg-transparent" wire:key="tv-leaderboard-{{ $row['id'] }}">
                            <td class="font-medium text-foreground {{ $typography['cell'] }}">{{ $row['name'] }}</td>
                            <td class="text-muted-foreground {{ $typography['cell'] }}">{{ $row['wins'] }}</td>
                            <td class="text-muted-foreground {{ $typography['cell'] }}">{{ $row['draws'] }}</td>
                            <td class="text-muted-foreground {{ $typography['cell'] }}">{{ $row['losses'] }}</td>
                            <td class="font-semibold text-foreground {{ $typography['cell'] }}">{{ $row['points'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-muted-foreground {{ $typography['cell'] }}" colspan="5">
                                Još nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>