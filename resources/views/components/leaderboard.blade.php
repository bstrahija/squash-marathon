@props([
    'leaderboard' => [],
])

<div class="rounded-3xl border border-border bg-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Leaderboard</p>
            <h2 class="mt-2 text-2xl font-semibold">Points after every game</h2>
        </div>
        <div class="text-xs text-muted-foreground">Win = 2 pts, Loss = 1 pt</div>
    </div>
    <div class="mt-6 overflow-hidden rounded-2xl border border-border/70">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-background/80 text-xs uppercase tracking-widest text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Player</th>
                        <th class="px-4 py-3">Wins</th>
                        <th class="px-4 py-3">Losses</th>
                        <th class="px-4 py-3">Points</th>
                        <th class="px-4 py-3">Last game</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/70">
                    @forelse ($leaderboard as $row)
                        <tr class="bg-card">
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
                                No games logged yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
