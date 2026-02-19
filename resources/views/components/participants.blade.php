@php
    $participants = [
        ['name' => 'Luka Matic', 'tag' => 'Power server'],
        ['name' => 'Ana Kovac', 'tag' => 'Fast retriever'],
        ['name' => 'Matej Blaz', 'tag' => 'Precision shots'],
        ['name' => 'Sara Horvat', 'tag' => 'Endurance ace'],
        ['name' => 'Ivan Zoric', 'tag' => 'Tactical reader'],
        ['name' => 'Petra Lukic', 'tag' => 'Creative angles'],
        ['name' => 'Marko Knez', 'tag' => 'Court sprinter'],
        ['name' => 'Nina Vuk', 'tag' => 'Calm closer'],
        ['name' => 'Marija Novak', 'tag' => 'Quick striker'],
        ['name' => 'Dario Bilic', 'tag' => 'Back wall control'],
        ['name' => 'Tina Radic', 'tag' => 'Relentless pace'],
        ['name' => 'Filip Peric', 'tag' => 'Angle hunter'],
        ['name' => 'Klara Petrovic', 'tag' => 'Cool under pressure'],
        ['name' => 'Josip Marin', 'tag' => 'Explosive starts'],
        ['name' => 'Ivana Soldo', 'tag' => 'Late-game finisher'],
    ];
@endphp

<div class="rounded-3xl border border-border bg-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Participants</p>
            <h2 class="mt-2 text-2xl font-semibold">The 24-hour roster</h2>
        </div>
        <p class="text-sm text-muted-foreground">Rotating across two courts all night.</p>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($participants as $participant)
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-xs font-semibold text-primary-foreground">
                    {{ strtoupper(substr($participant['name'], 0, 1)) }}
                </div>
                <p class="mt-3 text-sm font-semibold">{{ $participant['name'] }}</p>
                <p class="text-xs text-muted-foreground">{{ $participant['tag'] }}</p>
            </div>
        @endforeach
    </div>
</div>
