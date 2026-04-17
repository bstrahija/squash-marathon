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
                $profileUrl = $participant['profile_url'] ?? null;
                $summary =
                    $participant['games'] > 0
                        ? "W: <strong>{$participant['wins']}</strong> · D: <strong>{$participant['draws']}</strong> · L: <strong>{$participant['losses']}</strong>"
                        : 'Još bez odigranih';
            @endphp
            <div class="rounded-2xl border border-border/70 bg-background/70 p-4">
                @if (filled($profileUrl))
                    <a href="{{ $profileUrl }}"
                        class="group block rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/70 focus-visible:ring-offset-2 focus-visible:ring-offset-background">
                        <x-player-avatar :player="$participant" />
                        <p
                            class="mt-3 text-sm font-semibold transition group-hover:text-emerald-600 group-hover:underline dark:group-hover:text-emerald-400">
                            {{ $participant['name'] }}
                        </p>
                    </a>
                @else
                    <x-player-avatar :player="$participant" />
                    <p class="mt-3 text-sm font-semibold">{{ $participant['name'] }}</p>
                @endif
                <p class="text-xs text-muted-foreground">{!! $summary !!}</p>
            </div>
        @empty
            <div
                class="rounded-2xl border border-dashed border-border/70 bg-background/70 p-6 text-sm text-muted-foreground">
                Još nema ekipe.
            </div>
        @endforelse
    </div>
</div>
