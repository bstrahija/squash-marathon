@props([
    'participants' => [],
])

<div class="rounded-3xl border border-border bg-card p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Ekipa</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Svi igrači</h2>
        </div>
        <p class="text-sm text-muted-foreground">Rotiramo se na dva terena cijelu noć.</p>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @forelse ($participants as $participant)
            @php
                $summary =
                    $participant['games'] > 0
                        ? "{$participant['wins']} pobjede · {$participant['draws']} remija · {$participant['losses']} poraza"
                        : 'Još bez odigranih';
            @endphp
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4">
                <x-player-avatar :initials="$participant['initials'] ?? null" />
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
