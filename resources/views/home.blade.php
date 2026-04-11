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

    @if (session('status'))
        <div x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms
            class="pointer-events-none fixed inset-x-0 top-24 z-50 flex justify-center px-4">
            <div
                class="pointer-events-auto flex items-center gap-3 rounded-2xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-700 shadow-sm dark:text-emerald-300">
                <span>{{ session('status') }}</span>
                <button type="button" x-on:click="open = false"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-emerald-500/30 bg-transparent transition hover:bg-emerald-500/10"
                    aria-label="Zatvori obavijest">
                    <x-heroicon-o-x-mark class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    @endif

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
