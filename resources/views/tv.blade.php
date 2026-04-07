<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TV prikaz</title>
    @vite(['resources/css/app.css', 'resources/css/tv.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="tv-body bg-background text-foreground overflow-hidden">
    <div id="tv-container" class="tv-container w-screen overflow-hidden bg-background px-6 py-6 text-foreground lg:p-4">
        <div class="tv-grid grid h-full grid-cols-[2fr_1fr_1fr] gap-4">
            <section
                class="tv-panel-live col-start-1 row-span-6 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <div class="grid h-full min-h-0 grid-rows-2 gap-4 p-4">
                    <div class="min-h-0 rounded-3xl border border-border bg-card/70 shadow-sm">
                        <livewire:tv.group-match :group-number="1" wire:poll.3s />
                    </div>
                    <div class="min-h-0 rounded-3xl border border-border bg-card/70 shadow-sm">
                        <livewire:tv.group-match :group-number="2" wire:poll.3s />
                    </div>
                </div>
            </section>

            <section
                class="tv-panel-recent col-start-2 row-span-6 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <div class="flex h-full min-h-0 flex-col">
                    <div
                        class="border-b border-border/70 px-4 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Recent Matches
                    </div>
                    <div class="min-h-0 flex-1">
                        <livewire:tv.latest-games wire:poll.3s />
                    </div>
                </div>
            </section>

            <section class="tv-panel-right col-start-3 row-span-6 min-h-0">
                <div class="flex h-full min-h-0 flex-col gap-4">
                    <div class="min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                        <livewire:tv.event-end-countdown wire:poll.5s />
                    </div>
                    <div class="min-h-0 flex-1 rounded-3xl border border-border bg-card shadow-sm">
                        <livewire:tv.leaderboard wire:poll.20s />
                    </div>
                </div>
            </section>
        </div>
    </div>

    @livewireScripts
</body>

</html>
