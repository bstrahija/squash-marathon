<x-layouts.public-page title="Squash Marathon - Mečevi"
                       main-class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-12 pt-32">
    <x-slot:background>
        <div
             class="w-104 h-104 pointer-events-none absolute -left-32 -top-16 rounded-full bg-emerald-400/30 blur-3xl dark:bg-emerald-500/15">
        </div>
        <div
             class="w-88 h-88 pointer-events-none absolute -right-24 top-10 rounded-full bg-amber-300/35 blur-3xl dark:bg-amber-400/15">
        </div>
        <div
             class="top-88 pointer-events-none absolute left-1/2 h-72 w-[18rem] -translate-x-1/2 rounded-full bg-sky-300/25 blur-3xl dark:bg-sky-400/10">
        </div>
    </x-slot:background>

    <section class="scroll-mt-24">
        <div class="m-6 flex items-center justify-between gap-4">
            <h1 class="font-display text-foreground text-3xl font-semibold">Mečevi</h1>
            @auth
                <a href="{{ route('matches.create') }}"
                   class="bg-card border-border hover:border-foreground/40 text-foreground rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide transition hover:-translate-y-0.5">
                    Dodaj
                </a>
            @endauth
        </div>

        <livewire:matches-list />
    </section>
</x-layouts.public-page>
