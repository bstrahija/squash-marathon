@php
    $leaderboard = [
        ['name' => 'Ana Kovac', 'wins' => 8, 'losses' => 4, 'last' => '21:14'],
        ['name' => 'Luka Matic', 'wins' => 7, 'losses' => 5, 'last' => '21:02'],
        ['name' => 'Petra Lukic', 'wins' => 7, 'losses' => 5, 'last' => '21:01'],
        ['name' => 'Matej Blaz', 'wins' => 6, 'losses' => 6, 'last' => '20:51'],
        ['name' => 'Sara Horvat', 'wins' => 6, 'losses' => 6, 'last' => '20:47'],
        ['name' => 'Nina Vuk', 'wins' => 6, 'losses' => 5, 'last' => '20:45'],
        ['name' => 'Ivan Zoric', 'wins' => 5, 'losses' => 7, 'last' => '20:40'],
        ['name' => 'Marija Novak', 'wins' => 5, 'losses' => 6, 'last' => '20:36'],
        ['name' => 'Dario Bilic', 'wins' => 5, 'losses' => 6, 'last' => '20:30'],
        ['name' => 'Tina Radic', 'wins' => 4, 'losses' => 7, 'last' => '20:28'],
        ['name' => 'Filip Peric', 'wins' => 4, 'losses' => 7, 'last' => '20:22'],
        ['name' => 'Klara Petrovic', 'wins' => 4, 'losses' => 6, 'last' => '20:18'],
        ['name' => 'Marko Knez', 'wins' => 3, 'losses' => 8, 'last' => '20:12'],
        ['name' => 'Josip Marin', 'wins' => 3, 'losses' => 8, 'last' => '20:09'],
        ['name' => 'Ivana Soldo', 'wins' => 3, 'losses' => 7, 'last' => '20:03'],
    ];
@endphp

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
                    @foreach ($leaderboard as $row)
                        @php
                            $points = $row['wins'] * 2 + $row['losses'];
                        @endphp
                        <tr class="bg-card">
                            <td class="px-4 py-3 font-semibold text-foreground">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $row['wins'] }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $row['losses'] }}</td>
                            <td class="px-4 py-3 font-semibold text-foreground">{{ $points }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ $row['last'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
