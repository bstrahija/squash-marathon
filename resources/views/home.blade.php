<x-layouts.public-page title="Squash Marathon"
    main-class="mx-auto flex w-full max-w-6xl flex-col gap-16 px-6 pb-12 pt-32">
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
        <div
            class="hidden lg:block top-72 left-8 absolute bg-emerald-400/5 dark:bg-emerald-500/5 border border-emerald-400/30 dark:border-emerald-400/10 rounded-[2.5rem] w-64 h-64 rotate-6 pointer-events-none">
        </div>
    </x-slot:background>

    @if (session('status'))
        <div x-data="{ open: true }" x-show="open" x-transition.opacity.duration.200ms
            class="top-24 z-50 fixed inset-x-0 flex justify-center px-4 pointer-events-none">
            <div
                class="flex items-center gap-3 bg-emerald-400/10 shadow-sm px-4 py-3 border border-emerald-400/40 rounded-2xl font-semibold text-emerald-700 dark:text-emerald-300 text-sm pointer-events-auto">
                <span>{{ session('status') }}</span>
                <button type="button" x-on:click="open = false"
                    class="inline-flex justify-center items-center bg-transparent hover:bg-emerald-500/10 border border-emerald-500/30 rounded-full w-7 h-7 transition"
                    aria-label="Zatvori obavijest">
                    <x-heroicon-o-x-mark class="w-4 h-4" aria-hidden="true" />
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
