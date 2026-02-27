@props([
    'participants' => [],
])

<div class="rounded-3xl border border-border bg-card p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Ekipa</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Lista za 24 sata</h2>
        </div>
        <p class="text-sm text-muted-foreground">Rotiramo se na dva terena cijelu noć.</p>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($participants as $participant)
            @php
                $initials = collect(explode(' ', $participant['name'] ?? ''))
                    ->filter()
                    ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                    ->take(2)
                    ->join('');
                $summary =
                    $participant['games'] > 0
                        ? "{$participant['wins']} pobjede · {$participant['losses']} porazi"
                        : 'Još bez odigranih';
            @endphp
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-xs font-semibold text-primary-foreground">
                    {{ $initials !== '' ? $initials : '—' }}
                </div>
                <p class="mt-3 text-sm font-semibold">{{ $participant['name'] }}</p>
                <p class="text-xs text-muted-foreground">{{ $summary }}</p>
            </div>
        @empty
            <div
                class="rounded-2xl border border-dashed border-border/70 bg-background/70 p-6 text-sm text-muted-foreground">
                Još nema ekipe.
            </div>
        @endforelse
    </div>
</div>
