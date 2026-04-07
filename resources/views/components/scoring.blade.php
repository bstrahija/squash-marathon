<section class="rounded-3xl border border-border bg-card p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Bodovanje, ukratko</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Kako bodovi sjedaju</h2>
        </div>
        <p class="text-xs text-muted-foreground">Svaki završeni meč donosi bodove i vrijeme.</p>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="relative rounded-2xl border border-border/70 bg-background/70 p-6">
            <div class="absolute left-6 top-10 h-[calc(100%-5rem)] w-px bg-border/70"></div>
            <div class="grid gap-6">
                <div class="relative pl-10">
                    <span
                        class="absolute left-2 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-400/20 text-xs font-semibold text-emerald-700 dark:text-emerald-300">1</span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Igra gotova</p>
                    <p class="mt-2 text-sm font-semibold">Dva seta do 11</p>
                    <p class="mt-1 text-xs text-muted-foreground">Remi je moguć nakon 1-1.</p>
                </div>
                <div class="relative pl-10">
                    <span
                        class="absolute left-2 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-sky-400/20 text-xs font-semibold text-sky-700 dark:text-sky-300">2</span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Bodovi idu</p>
                    <p class="mt-2 text-sm font-semibold">Pobjeda = 3 boda, remi = 2 boda, poraz = 1 bod</p>
                    <p class="mt-1 text-xs text-muted-foreground">Sve ide ravno u poredak.</p>
                </div>
                <div class="relative pl-10">
                    <span
                        class="absolute left-2 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-amber-400/20 text-xs font-semibold text-amber-700 dark:text-amber-300">3</span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Vrijeme</p>
                    <p class="mt-2 text-sm font-semibold">Upis u vremensku crtu</p>
                    <p class="mt-1 text-xs text-muted-foreground">Bilježimo vrijeme završetka.</p>
                </div>
            </div>

        </div>
        <div class="rounded-2xl border border-border/70 bg-background/70 p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Brzi primjer</p>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between rounded-xl border border-border/70 bg-card px-3 py-2">
                    <span class="font-semibold">Vedran pobjeđuje 2-0</span>
                    <span class="text-muted-foreground">+3 boda</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-border/70 bg-card px-3 py-2">
                    <span class="font-semibold">Igor remizira 1-1</span>
                    <span class="text-muted-foreground">+2 boda</span>
                </div>
                <div class="flex items-center justify-between rounded-xl border border-border/70 bg-card px-3 py-2">
                    <span class="font-semibold">Upisano</span>
                    <span class="text-muted-foreground">19:45 vrijeme</span>
                </div>
            </div>
            <div class="mt-5 rounded-2xl border border-border/70 bg-card p-4 text-xs text-muted-foreground">
                Svaka završena igra ažurira poredak i dodaje unos u vremensku crtu. Trajanje nam ne treba.
            </div>
        </div>
    </div>
</section>
