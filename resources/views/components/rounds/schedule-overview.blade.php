@props([
    'groups' => collect(),
    'schedulesByGroup' => collect(),
    'linkToScoring' => false,
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
                            @php($isLive = (bool) $schedule->game?->isLive())
                            @php($canLinkToScore = $linkToScoring && auth()->check())
                            @php($latestSet = $schedule->game?->sets?->sortBy('created_at')->last())
                            @php($latestLog = $schedule->game?->gameLogs?->sortBy('sequence')->last())
                            @php($playerOneSets = (int) ($schedule->game?->player_one_sets ?? 0))
                            @php($playerTwoSets = (int) ($schedule->game?->player_two_sets ?? 0))
                            @php($playerOnePoints = (int) ($latestLog?->player_one_score ?? ($latestSet?->player_one_score ?? 0)))
                            @php($playerTwoPoints = (int) ($latestLog?->player_two_score ?? ($latestSet?->player_two_score ?? 0)))
                            @if ($canLinkToScore)
                                <a href="{{ route('schedule.play', ['gameSchedule' => $schedule->id]) }}"
                                    @if (! $schedule->game)
                                        onclick="return confirm('Otvoriti bodovanje ovog meča? Ako meč još ne postoji, bit će kreiran.')"
                                    @endif
                                    class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 py-2 first:pt-0 last:pb-0 transition hover:bg-muted/40 {{ $isLive ? 'rounded-lg bg-emerald-500/10 px-2' : '' }}">
                                    <span class="truncate text-sm font-semibold text-foreground">
                                        {{ $schedule->playerOne?->short_name ?? ($schedule->playerOne?->full_name ?? '—') }}
                                    </span>
                                    @if ($schedule->game)
                                        <span
                                            class="text-center font-semibold tabular-nums {{ $isLive ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground' }}">
                                            <span class="text-[10px]">({{ $playerOneSets }})</span>
                                            {{ $playerOnePoints }}:{{ $playerTwoPoints }} <span
                                                class="text-[10px]">({{ $playerTwoSets }})</span>
                                        </span>
                                    @else
                                        <span
                                            class="text-center text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                            vs
                                        </span>
                                    @endif
                                    <span class="truncate text-right text-sm font-semibold text-foreground">
                                        {{ $schedule->playerTwo?->short_name ?? ($schedule->playerTwo?->full_name ?? '—') }}
                                    </span>
                                </a>
                            @else
                                <div
                                    class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 py-2 first:pt-0 last:pb-0 {{ $isLive ? 'rounded-lg bg-emerald-500/10 px-2' : '' }}">
                                    <span class="truncate text-sm font-semibold text-foreground">
                                        {{ $schedule->playerOne?->short_name ?? ($schedule->playerOne?->full_name ?? '—') }}
                                    </span>
                                    @if ($schedule->game)
                                        <span
                                            class="text-center font-semibold tabular-nums {{ $isLive ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground' }}">
                                            <span class="text-[10px]">({{ $playerOneSets }})</span>
                                            {{ $playerOnePoints }}:{{ $playerTwoPoints }} <span
                                                class="text-[10px]">({{ $playerTwoSets }})</span>
                                        </span>
                                    @else
                                        <span
                                            class="text-center text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                            vs
                                        </span>
                                    @endif
                                    <span class="truncate text-right text-sm font-semibold text-foreground">
                                        {{ $schedule->playerTwo?->short_name ?? ($schedule->playerTwo?->full_name ?? '—') }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
