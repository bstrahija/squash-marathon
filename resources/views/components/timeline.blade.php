@props([
    'timeline' => [],
])

<div class="rounded-3xl border border-border bg-card p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Kronologija</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Najsvježije završene partije</h2>
        </div>
        <p class="text-xs text-muted-foreground">Zadnjih 24 završenih partija.</p>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($timeline as $entry)
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4">
                <div class="flex items-center justify-between text-xs font-semibold text-muted-foreground">
                    <span>{{ $entry['time']?->format('H:i') ?? '—' }}</span>
                    <span
                        class="rounded-full border border-border/70 bg-card px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-foreground">
                        Kraj
                    </span>
                </div>
                <p class="mt-3 text-sm font-semibold text-foreground">{{ $entry['game'] }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Rezultat</p>
                <p class="mt-1 text-sm font-semibold text-foreground">{{ $entry['score'] }}</p>
            </div>
        @empty
            <div
                class="rounded-2xl border border-dashed border-border/70 bg-background/70 px-4 py-6 text-sm text-muted-foreground">
                Još nema završenih partija.
            </div>
        @endforelse
    </div>
</div>
