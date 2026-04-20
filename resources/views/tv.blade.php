<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TV prikaz</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}" />
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}" />
    @vite(['resources/css/app.css', 'resources/css/tv.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="bg-background overflow-hidden text-foreground tv-body">
    <div class="landscape:hidden">
        <x-nav />
    </div>

    <div id="tv-container"
        class="bg-background p-2 lg:p-3 md:px-4 md:py-4 w-screen portrait:h-auto landscape:h-svh min-h-svh overflow-hidden portrait:overflow-visible landscape:overflow-hidden text-foreground tv-container">
        <div
            class="gap-4 grid tv-grid grid-cols-[2fr_1fr_1fr] grid-rows-6 h-full portrait:h-auto landscape:h-full portrait:min-h-svh">
            <section class="col-start-1 row-span-full min-h-0 tv-panel-live portrait:row-auto">
                <div class="gap-4 grid grid-rows-2 h-full min-h-0">
                    <div class="bg-card/70 shadow-sm border border-border rounded-3xl min-h-0">
                        <livewire:tv.group-match :group-number="1" :key="'tv-group-match-1'" />
                    </div>
                    <div class="bg-card/70 shadow-sm border border-border rounded-3xl min-h-0">
                        <livewire:tv.group-match :group-number="2" :key="'tv-group-match-2'" />
                    </div>
                </div>
            </section>

            <section class="tv-panel-group-leaderboards col-start-2 row-span-full min-h-0 portrait:row-auto">
                <div class="gap-4 grid grid-rows-2 h-full min-h-0">
                    <div class="bg-card shadow-sm border border-border rounded-3xl min-h-0">
                        <livewire:tv.group-leaderboard :group-number="1" :key="'tv-group-leaderboard-1'" />
                    </div>
                    <div class="bg-card shadow-sm border border-border rounded-3xl min-h-0">
                        <livewire:tv.group-leaderboard :group-number="2" :key="'tv-group-leaderboard-2'" />
                    </div>
                </div>
            </section>

            <section class="tv-panel-right col-start-3 row-span-full min-h-0 portrait:row-auto">
                <div class="flex flex-col gap-4 h-full min-h-0">
                    <div class="bg-card shadow-sm border border-border rounded-3xl min-h-0 tv-panel-countdown">
                        <livewire:tv.event-end-countdown />
                    </div>
                    <div class="flex-1 bg-card shadow-sm border border-border rounded-3xl min-h-0 tv-panel-recent">
                        <div class="flex flex-col h-full min-h-0">
                            <div
                                class="px-4 py-3 border-border/70 border-b font-semibold text-muted-foreground uppercase tracking-[0.14em] tv-recent-heading">
                                Recent Matches
                            </div>
                            <div class="flex-1 min-h-0">
                                <livewire:tv.latest-games />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @livewireScripts
</body>

</html>
