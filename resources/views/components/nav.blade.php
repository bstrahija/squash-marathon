<nav class="fixed inset-x-0 top-0 z-40 border-b border-border/70 bg-background/80 backdrop-blur">
    <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
        <a class="flex items-center gap-3" href="{{ route('home') }}">
            <span
                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-br from-emerald-500 via-lime-500 to-amber-400 text-xs font-semibold text-black shadow-sm">
                SM
            </span>
            <div>
                <p class="text-sm font-semibold text-foreground">Squash Marathon</p>
                <p class="text-xs text-muted-foreground">24 sata u pokretu</p>
            </div>
        </a>
        <div class="hidden items-center gap-6 text-sm md:flex">
            <a class="text-muted-foreground transition hover:text-foreground" href="{{ route('home') }}">O eventu</a>
            <a class="text-muted-foreground transition hover:text-foreground"
                href="{{ route('matches.index') }}">Mečevi</a>
            <a class="text-muted-foreground transition hover:text-foreground"
                href="{{ route('rounds.index') }}">Runde</a>
            <a class="text-muted-foreground transition hover:text-foreground"
                href="{{ route('home') }}#participants">Ekipa</a>
            <a class="text-muted-foreground transition hover:text-foreground"
                href="{{ route('home') }}#leaderboard">Poredak</a>
            <a class="text-muted-foreground transition hover:text-foreground"
                href="{{ route('home') }}#timeline">Kronologija</a>
        </div>
        <div class="flex items-center gap-3">
            <button aria-label="Toggle theme" aria-pressed="false"
                class="relative flex h-10 w-10 items-center justify-center rounded-full border border-border bg-card text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40"
                data-theme-toggle title="Toggle theme" type="button">
                <svg aria-hidden="true"
                    class="absolute inset-0 m-auto h-4 w-4 scale-100 opacity-100 transition duration-300"
                    data-theme-icon="sun" fill="none" viewBox="0 0 24 24">
                    <path
                        d="M12 4.5v-2M12 21.5v-2M4.5 12h-2M21.5 12h-2M6.2 6.2l-1.4-1.4M19.2 19.2l-1.4-1.4M6.2 17.8l-1.4 1.4M19.2 4.8l-1.4 1.4"
                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                    <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5" />
                </svg>
                <svg aria-hidden="true"
                    class="absolute inset-0 m-auto h-4 w-4 scale-75 opacity-0 transition duration-300"
                    data-theme-icon="moon" fill="none" viewBox="0 0 24 24">
                    <path d="M20 14.5A7.5 7.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z" stroke="currentColor"
                        stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                </svg>
            </button>

            <a class="rounded-full border border-border bg-card px-4 py-2 text-xs font-semibold uppercase tracking-wide text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40"
                href="{{ route('tv') }}">
                Live
            </a>
        </div>
    </div>
</nav>
