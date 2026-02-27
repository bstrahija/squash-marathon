@props([
    'timeline' => [],
])

<div class="rounded-3xl border border-border bg-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Timeline</p>
            <h2 class="mt-2 text-2xl font-semibold">Recent finished games</h2>
        </div>
        <p class="text-xs text-muted-foreground">Last 20 completed games.</p>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($timeline as $entry)
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4">
                <div class="flex items-center justify-between text-xs font-semibold text-muted-foreground">
                    <span>{{ $entry['time']?->format('H:i') ?? '—' }}</span>
                    <span
                        class="rounded-full border border-border/70 bg-card px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-foreground">
                        Final
                    </span>
                </div>
                <p class="mt-3 text-sm font-semibold text-foreground">{{ $entry['game'] }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Score</p>
                <p class="mt-1 text-sm font-semibold text-foreground">{{ $entry['score'] }}</p>
            </div>
        @empty
            <div
                class="rounded-2xl border border-dashed border-border/70 bg-background/70 px-4 py-6 text-sm text-muted-foreground">
                No finished games yet.
            </div>
        @endforelse
    </div>
</div>
