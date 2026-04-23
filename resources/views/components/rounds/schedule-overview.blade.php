@props([
    'groups' => collect(),
    'schedulesByGroup' => collect(),
])

<div class="mb-6">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Raspored</p>
    <h2 class="mt-2 font-display text-2xl font-semibold text-foreground">Planirani mečevi</h2>

    <div class="mt-3 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($groups as $group)
            @php($groupSchedules = $schedulesByGroup->get((int) $group->id, collect()))

            <div class="rounded-2xl border border-border/70 bg-card/50 p-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                    {{ $group->name }}
                </p>

                @if ($groupSchedules->isEmpty())
                    <p class="text-sm text-muted-foreground">Raspored još nije kreiran.</p>
                @else
                    <div class="divide-y divide-border/50">
                        @foreach ($groupSchedules as $schedule)
                            <div
                                class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 py-2 first:pt-0 last:pb-0">
                                <span class="truncate text-sm font-semibold text-foreground">
                                    {{ $schedule->playerOne?->short_name ?? ($schedule->playerOne?->full_name ?? '—') }}
                                </span>
                                <span
                                    class="text-center text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">vs</span>
                                <span class="truncate text-right text-sm font-semibold text-foreground">
                                    {{ $schedule->playerTwo?->short_name ?? ($schedule->playerTwo?->full_name ?? '—') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
