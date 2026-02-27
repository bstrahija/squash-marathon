@props([
    'participants' => [],
])

<div class="rounded-3xl border border-border bg-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Participants</p>
            <h2 class="mt-2 text-2xl font-semibold">The 24-hour roster</h2>
        </div>
        <p class="text-sm text-muted-foreground">Rotating across two courts all night.</p>
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
                        ? "{$participant['wins']} wins · {$participant['losses']} losses"
                        : 'No games yet';
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
                No participants yet.
            </div>
        @endforelse
    </div>
</div>
