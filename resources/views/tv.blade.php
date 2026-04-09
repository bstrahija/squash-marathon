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
    <div id="tv-container"
        class="tv-container w-screen overflow-hidden bg-background p-2 text-foreground md:px-6 md:py-6 lg:p-4">
        <div class="tv-grid grid h-full grid-cols-[2fr_1fr_1fr] gap-4">
            <section class="tv-panel-live min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <div class="grid h-full min-h-0 grid-rows-2 gap-4 p-2 md:p-4">
                    <div class="min-h-0 rounded-3xl border border-border bg-card/70 shadow-sm">
                        <livewire:tv.group-match :group-number="1" :key="'tv-group-match-1'" />
                    </div>
                    <div class="min-h-0 rounded-3xl border border-border bg-card/70 shadow-sm">
                        <livewire:tv.group-match :group-number="2" :key="'tv-group-match-2'" />
                    </div>
                </div>
            </section>

            <section class="tv-panel-recent min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <div class="flex h-full min-h-0 flex-col">
                    <div
                        class="tv-recent-heading border-b border-border/70 px-4 py-3 font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                        Recent Matches
                    </div>
                    <div class="min-h-0 flex-1">
                        <livewire:tv.latest-games />
                    </div>
                </div>
            </section>

            <section class="tv-panel-right min-h-0">
                <div class="flex h-full min-h-0 flex-col gap-4">
                    <div class="tv-panel-countdown min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                        <livewire:tv.event-end-countdown />
                    </div>
                    <div class="tv-panel-leaderboard min-h-0 flex-1 rounded-3xl border border-border bg-card shadow-sm">
                        <livewire:tv.leaderboard />
                    </div>
                </div>
            </section>
        </div>
    </div>

    @livewireScripts
</body>

</html>
