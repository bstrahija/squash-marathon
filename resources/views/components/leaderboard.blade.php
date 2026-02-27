@props([
    'leaderboard' => [],
])

<div class="rounded-3xl border border-border bg-card p-6 shadow-sm">
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
                    @forelse ($leaderboard as $row)
                        <tr class="bg-card transition hover:bg-emerald-400/5">
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
