<section class="rounded-3xl border border-border bg-card p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Scoring flow</p>
            <h2 class="mt-2 text-2xl font-semibold">How scoring works</h2>
        </div>
        <p class="text-xs text-muted-foreground">Every finished game creates points and a timestamp.</p>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="relative rounded-2xl border border-border/70 bg-background/70 p-6">
            <div class="absolute left-6 top-10 h-[calc(100%-5rem)] w-px bg-border/70"></div>
            <div class="grid gap-6">
                <div class="relative pl-10">
                    <span class="absolute left-2 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-400/20 text-xs font-semibold text-emerald-700 dark:text-emerald-300">1</span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Game ends</p>
                    <p class="mt-2 text-sm font-semibold">Single game to 11 points</p>
                    <p class="mt-1 text-xs text-muted-foreground">Winner and loser are locked in.</p>
                </div>
                <div class="relative pl-10">
                    <span class="absolute left-2 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-sky-400/20 text-xs font-semibold text-sky-700 dark:text-sky-300">2</span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Points awarded</p>
                    <p class="mt-2 text-sm font-semibold">Win = 2 points, Loss = 1 point</p>
                    <p class="mt-1 text-xs text-muted-foreground">Totals roll into the leaderboard.</p>
                </div>
                <div class="relative pl-10">
                    <span class="absolute left-2 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400/20 text-xs font-semibold text-amber-700 dark:text-amber-300">3</span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Timestamp</p>
                    <p class="mt-2 text-sm font-semibold">Timeline entry created</p>
                    <p class="mt-1 text-xs text-muted-foreground">We capture the finish time only.</p>
                </div>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-border/70 bg-card px-4 py-3 text-xs text-muted-foreground">
                <span class="rounded-full bg-amber-400/20 px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Timeline</span>
                <span>Example: 19:45 game end stored.</span>
            </div>
        </div>
        <div class="rounded-2xl border border-border/70 bg-background/70 p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Example snapshot</p>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between rounded-xl border border-border/70 bg-card px-3 py-2">
                    <span class="font-semibold">Ana wins 11-8</span>
                    <span class="text-muted-foreground">+2 pts</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-border/70 bg-card px-3 py-2">
                    <span class="font-semibold">Luka loses 8-11</span>
                    <span class="text-muted-foreground">+1 pt</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-border/70 bg-card px-3 py-2">
                    <span class="font-semibold">Recorded</span>
                    <span class="text-muted-foreground">19:45 timestamp</span>
                </div>
            </div>
            <div class="mt-5 rounded-2xl border border-border/70 bg-card p-4 text-xs text-muted-foreground">
                Each finished game updates the leaderboard and adds a timeline entry. No game durations needed.
            </div>
        </div>
    </div>
</section>
