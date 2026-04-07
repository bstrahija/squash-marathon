<x-layouts.public-page title="Squash Marathon"
    main-class="mx-auto flex w-full max-w-6xl flex-col gap-16 px-6 pb-12 pt-32">
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
        <div
            class="pointer-events-none absolute left-8 top-72 hidden h-64 w-64 rotate-6 rounded-[2.5rem] border border-emerald-400/30 bg-emerald-400/5 dark:border-emerald-400/10 dark:bg-emerald-500/5 lg:block">
        </div>
    </x-slot:background>

    <x-hero />

    <section id="about" class="scroll-mt-24">
        <x-event-info />
    </section>

    <section id="participants" class="scroll-mt-24">
        <x-participants :participants="$participants" />
    </section>

    <section id="leaderboard" class="scroll-mt-24">
        <livewire:leaderboard wire:poll.20s />
    </section>

    <section id="timeline" class="scroll-mt-24">
        <livewire:timeline wire:poll.20s />
    </section>
</x-layouts.public-page>
