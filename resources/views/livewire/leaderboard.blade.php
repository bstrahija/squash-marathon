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
                    'losses' => 0,
                    'games' => 0,
                    'last_game_at' => null,
                ],
            ];
        });

        foreach ($games as $game) {
            $winnerId = Game::determineWinnerIdFromSetScores(
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

            if (!$winnerId) {
                continue;
            }

            foreach ([$game->player_one_id, $game->player_two_id] as $playerId) {
                if (!$stats->has($playerId)) {
                    continue;
                }

                $row = $stats->get($playerId);
                $row['games'] += 1;

                if ($playerId === $winnerId) {
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
                    'losses' => $row['losses'],
                    'points' => $row['wins'] * 2 + $row['losses'],
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
};
?>

<div class="rounded-3xl border border-border bg-card p-6 shadow-sm" wire:poll.5s>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Poredak</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Bodovi nakon svake partije</h2>
        </div>
        <div class="text-xs text-muted-foreground">Pobjeda = 2 boda, poraz = 1 bod</div>
    </div>
    <div class="mt-6 overflow-hidden rounded-2xl border border-border/70">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead
                    class="bg-gradient-to-r from-emerald-400/15 via-amber-400/10 to-sky-400/15 text-xs uppercase tracking-widest text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Igrač</th>
                        <th class="px-4 py-3">Pobjede</th>
                        <th class="px-4 py-3">Porazi</th>
                        <th class="px-4 py-3">Bodovi</th>
                        <th class="px-4 py-3">Zadnja partija</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/70">
                    @forelse ($this->leaderboard as $row)
                        <tr class="bg-card transition hover:bg-emerald-400/5"
                            wire:key="leaderboard-{{ $row['id'] }}">
                            <td class="px-4 py-3 font-semibold text-foreground">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $row['wins'] }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $row['losses'] }}</td>
                            <td class="px-4 py-3 font-semibold text-foreground">{{ $row['points'] }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ $row['last_game_at']?->format('H:i') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-card">
                            <td class="px-4 py-6 text-center text-sm text-muted-foreground" colspan="5">
                                Još nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
