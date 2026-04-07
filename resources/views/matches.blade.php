<x-layouts.public-page title="Squash Marathon - Mečevi"
    main-class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-12 pt-32">
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

    <section class="scroll-mt-24">
        <h1 class="font-display m-6 text-3xl font-semibold text-foreground">Lista svih mečeva</h1>

        <livewire:matches-list />
    </section>
</x-layouts.public-page>
