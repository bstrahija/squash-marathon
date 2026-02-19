@php
    $timeline = [
        ['time' => '19:45', 'game' => 'Luka vs Ana', 'score' => '11-8'],
        ['time' => '20:05', 'game' => 'Sara vs Matej', 'score' => '11-6'],
        ['time' => '20:24', 'game' => 'Petra vs Ivan', 'score' => '9-11'],
        ['time' => '20:42', 'game' => 'Nina vs Marko', 'score' => '11-9'],
    ];
@endphp

<div class="rounded-3xl border border-border bg-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Timeline</p>
            <h2 class="mt-2 text-2xl font-semibold">Recent finished games</h2>
        </div>
        <p class="text-xs text-muted-foreground">Timestamps appear when games end.</p>
    </div>
    <div class="mt-6 space-y-3">
        @foreach ($timeline as $entry)
            <div
                class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-border/70 bg-background/70 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-foreground">{{ $entry['game'] }}</p>
                    <p class="text-xs text-muted-foreground">Final score: {{ $entry['score'] }}</p>
                </div>
                <span class="text-sm font-semibold text-foreground">{{ $entry['time'] }}</span>
            </div>
        @endforeach
    </div>
</div>
