<x-layouts.public-page title="Squash Marathon - Mečevi"
    main-class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-12 pt-32">
    <x-slot:background>
        <div
            class="-top-16 -left-32 absolute bg-emerald-400/30 dark:bg-emerald-500/15 blur-3xl rounded-full w-104 h-104 pointer-events-none">
        </div>
        <div
            class="top-10 -right-24 absolute bg-amber-300/35 dark:bg-amber-400/15 blur-3xl rounded-full w-88 h-88 pointer-events-none">
        </div>
        <div
            class="top-88 left-1/2 absolute bg-sky-300/25 dark:bg-sky-400/10 blur-3xl rounded-full w-[18rem] h-72 -translate-x-1/2 pointer-events-none">
        </div>
    </x-slot:background>

    <section class="scroll-mt-24">
        <div class="flex justify-between items-center gap-4 m-6">
            <h1 class="font-display font-semibold text-foreground text-3xl">Mečevi</h1>
            @if (auth()->user()
                    ?->hasAnyRole([\App\Enums\RoleName::Admin->value, \App\Enums\RoleName::Player->value]))
                <a href="{{ route('matches.create') }}"
                    class="bg-card px-4 py-2 border border-border hover:border-foreground/40 rounded-full font-semibold text-foreground text-xs uppercase tracking-wide transition hover:-translate-y-0.5">
                    Dodaj
                </a>
            @endif
        </div>

        <livewire:matches-list />
    </section>
</x-layouts.public-page>
