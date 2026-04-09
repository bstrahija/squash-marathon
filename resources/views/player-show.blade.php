<x-layouts.public-page :title="'Igrac - ' . $player->full_name" main-class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-6 pb-12 pt-32">
    <x-slot:background>
        <div
            class="pointer-events-none absolute -left-32 -top-16 h-104 w-104 rounded-full bg-emerald-400/30 blur-3xl dark:bg-emerald-500/15">
        </div>
        <div
            class="pointer-events-none absolute -right-24 top-10 h-88 w-88 rounded-full bg-amber-300/35 blur-3xl dark:bg-amber-400/15">
        </div>
        <div
            class="pointer-events-none absolute left-1/2 top-88 h-72 w-[18rem] -translate-x-1/2 rounded-full bg-sky-300/25 blur-3xl dark:bg-sky-400/10">
        </div>
    </x-slot:background>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:items-start">
        <section class="order-2 space-y-6 lg:order-1">
            <header class="rounded-3xl border border-border bg-card/85 p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Profil igraca</p>
                <h1 class="font-display mt-2 text-3xl font-semibold text-foreground">{{ $player->full_name }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    @if ($event)
                        Mecovi za event: <span class="font-semibold text-foreground">{{ $event->name }}</span>
                    @else
                        Trenutno nema aktivnog eventa.
                    @endif
                </p>
            </header>

            <div class="rounded-3xl border border-border bg-card/85 p-6 shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Mecovi</p>
                        <h2 class="font-display mt-2 text-2xl font-semibold text-foreground">Mecovi igraca na trenutnom
                            eventu</h2>
                    </div>
                    <a href="{{ route('matches.index') }}"
                        class="rounded-full border border-border bg-background px-4 py-2 text-xs font-semibold uppercase tracking-wide text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40">
                        Svi mečevi
                    </a>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($matches as $game)
                        @php
                            $result = \App\Models\Game::determineMatchResultFromSetScores(
                                $game->sets
                                    ->map(
                                        fn($set): array => [
                                            'player_one_score' => $set->player_one_score,
                                            'player_two_score' => $set->player_two_score,
                                        ],
                                    )
                                    ->all(),
                                $game->best_of,
                                $game->player_one_id,
                                $game->player_two_id,
                            );

                            $isDraw = (bool) ($result['is_complete'] && $result['is_draw']);
                            $winnerId = $result['winner_id'] ?? null;
                            $statusType =
                                blank($game->started_at) && blank($game->finished_at)
                                    ? 'waiting'
                                    : (filled($game->started_at) && blank($game->finished_at)
                                        ? 'live'
                                        : 'finished');
                            $statusLabel = match ($statusType) {
                                'waiting' => 'NA CEKANJU',
                                'live' => 'UZIVO',
                                default => 'ZAVRSENO',
                            };
                            $statusBadgeClass = match ($statusType) {
                                'waiting' => 'border-amber-400/40 bg-amber-400/10 text-amber-700 dark:text-amber-300',
                                'live'
                                    => 'border-emerald-400/40 bg-emerald-400/10 text-emerald-700 dark:text-emerald-300',
                                default => 'border-sky-400/40 bg-sky-400/10 text-sky-700 dark:text-sky-300',
                            };
                            $scoreSummary = $game->sets
                                ->filter(
                                    fn($set): bool => filled($set->player_one_score) && filled($set->player_two_score),
                                )
                                ->map(fn($set): string => "{$set->player_one_score}-{$set->player_two_score}")
                                ->implode(', ');
                            $playerOneClass = 'text-foreground';
                            $playerTwoClass = 'text-foreground';

                            if ($result['is_complete']) {
                                if ($isDraw) {
                                    $playerOneClass = 'text-amber-600/90 dark:text-amber-400/90';
                                    $playerTwoClass = 'text-amber-600/90 dark:text-amber-400/90';
                                } elseif ($winnerId === $game->player_one_id) {
                                    $playerOneClass = 'text-emerald-600 dark:text-emerald-400';
                                    $playerTwoClass = 'text-foreground/70';
                                } elseif ($winnerId === $game->player_two_id) {
                                    $playerOneClass = 'text-foreground/70';
                                    $playerTwoClass = 'text-emerald-600 dark:text-emerald-400';
                                }
                            }
                        @endphp

                        <a href="{{ route('matches.score', ['game' => $game->id]) }}"
                            class="block rounded-2xl border border-border/70 bg-background/70 p-4 transition hover:-translate-y-0.5 hover:border-foreground/35 hover:bg-background">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span
                                    class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $statusBadgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                                <p class="text-xs text-muted-foreground">
                                    {{ $game->group?->name ?? '—' }} |
                                    {{ $game->created_at?->format('d.m.Y H:i') ?? '—' }}
                                </p>
                            </div>

                            <p class="mt-3 text-base font-semibold leading-tight">
                                <span class="{{ $playerOneClass }}">{{ $game->playerOne?->full_name ?? '—' }}</span>
                                <span class="text-muted-foreground">vs</span>
                                <span class="{{ $playerTwoClass }}">{{ $game->playerTwo?->full_name ?? '—' }}</span>
                            </p>

                            <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-muted-foreground">
                                    Setovi {{ $result['player_one_wins'] }}-{{ $result['player_two_wins'] }}
                                    @if ($scoreSummary !== '')
                                        | {{ $scoreSummary }}
                                    @endif
                                </p>
                                <p class="text-sm font-semibold text-foreground">
                                    {{ $game->duration_seconds ? sprintf('%d:%02d', intdiv($game->duration_seconds, 60), $game->duration_seconds % 60) : '—' }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div
                            class="rounded-2xl border border-border/70 bg-background/70 px-4 py-8 text-center text-sm text-muted-foreground">
                            Nema meceva za ovog igraca na trenutnom eventu.
                        </div>
                    @endforelse
                </div>

                <div class="mt-5">
                    {{ $matches->links() }}
                </div>
            </div>
        </section>

        <aside class="order-1 space-y-6 lg:order-2 lg:sticky lg:top-24">
            <div class="rounded-3xl border border-border bg-card/85 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <img src="{{ $player->avatarUrl('thumb') }}" alt="{{ $player->full_name }}"
                        class="h-20 w-20 rounded-2xl border border-border/70 object-cover shadow-sm" />
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Igrac</p>
                        <p class="font-display mt-1 text-2xl font-semibold text-foreground">{{ $player->full_name }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-xl border border-border/70 bg-background/70 p-3">
                        <p class="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">Pobjede</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">{{ $stats['wins'] }}</p>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-background/70 p-3">
                        <p class="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">Remiji</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">{{ $stats['draws'] }}</p>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-background/70 p-3">
                        <p class="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">Porazi</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">{{ $stats['losses'] }}</p>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-background/70 p-3">
                        <p class="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">Bodovi</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">{{ $stats['points'] }}</p>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-background/70 p-3 col-span-2">
                        <p class="text-[11px] uppercase tracking-[0.14em] text-muted-foreground">Mec u tijeku</p>
                        <p class="mt-1 text-xl font-semibold text-foreground">{{ $stats['in_progress'] }}</p>
                    </div>
                </div>
            </div>

            <livewire:leaderboard wire:poll.20s />
        </aside>
    </div>
</x-layouts.public-page>
