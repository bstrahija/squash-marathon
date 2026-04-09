<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Game;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
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
                    'name' => $row['player']->short_name,
                    'profile_url' => route('players.show', ['user' => $row['player']->id]),
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
