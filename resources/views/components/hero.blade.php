<section class="grid items-center gap-10 lg:grid-cols-[1.2fr_0.8fr]">
    <div class="space-y-6">
        <div
            class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
            <a href="https://www.facebook.com/squasharenacakovec" target="_blank" rel="noopener noreferrer"
                class="rounded-full border border-emerald-400/40 bg-emerald-400/10 px-3 py-1 text-emerald-700 dark:text-emerald-300">
                Squash Arena Čakovec
            </a> <span
                class="rounded-full border border-amber-400/40 bg-amber-400/10 px-3 py-1 text-amber-700 dark:text-amber-300">
                24. travnja, 17:00</span>
            <span class="rounded-full border border-sky-400/40 bg-sky-400/10 px-3 py-1 text-sky-700 dark:text-sky-300">24
                sata</span>
        </div>
        <h1 class="font-display text-3xl font-semibold leading-tight sm:text-4xl">
            Squash Marathon 24h
        </h1>
        <p class="max-w-xl text-base text-muted-foreground">
            Ultimativni test izdržljivosti u
            <a href="https://www.facebook.com/squasharenacakovec" target="_blank" rel="noopener noreferrer">
                Squash Areni Čakovec
            </a>
            koji će se pamtiti cijeli život (ili barem dok se upala mišića ne smiri).
        </p>
        <div class="flex flex-wrap items-center gap-4">
            <a class="rounded-full bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition hover:-translate-y-0.5"
                href="#participants">
                Upoznaj ekipu
            </a>
            <a class="rounded-full border border-border bg-card px-5 py-3 text-sm font-semibold text-foreground transition hover:-translate-y-0.5"
                href="#leaderboard">
                Skoči na poredak
            </a>
        </div>
        <livewire:hero-countdown />
    </div>
    <div class="rounded-3xl border border-border bg-card/90 p-6 shadow-xl">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Brzi pregled</p>
            <span
                class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                Pripremamo teren
            </span>
        </div>
        <div class="mt-6 grid gap-4">
            <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Format</p>
                <p class="mt-2 text-sm font-semibold">Svaki meč igra se na 2 seta (do 11 bodova).</p>
                <p class="mt-1 text-xs text-muted-foreground">Igra se po pravilima iz bloka Pravila igre.</p>
            </div>
            <div class="rounded-2xl border border-amber-400/30 bg-amber-400/10 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Bodovanje</p>
                <p class="mt-2 text-sm font-semibold">Pobjeda = 3 boda, remi = 2 boda, poraz = 1 bod.</p>
                <p class="mt-1 text-xs text-muted-foreground">Svaki odigrani meč donosi bodove.</p>
            </div>
            <div class="rounded-2xl border border-sky-400/30 bg-sky-400/10 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Poredak</p>
                <p class="mt-2 text-sm font-semibold">Bodovi se nakon svakog meča odmah upisuju.</p>
                <p class="mt-1 text-xs text-muted-foreground">Tablica se osvježava tijekom cijelog događaja.</p>
            </div>
        </div>
    </div>
</section>
