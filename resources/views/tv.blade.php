<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TV prikaz</title>
    <script>
        (function() {
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/css/tv.css'])
    @livewireStyles
</head>

<body class="tv-body bg-background text-foreground overflow-hidden">
    <div id="tv-container" class="tv-container w-screen overflow-hidden bg-background px-6 py-6 text-foreground lg:p-4">
        <div class="tv-grid grid h-full grid-cols-3 gap-4">
            <section
                class="tv-panel-group-1 col-span-2 row-span-3 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <livewire:tv.group-match :group-number="1" wire:poll.3s />
            </section>
            <section
                class="tv-panel-group-2 col-span-2 row-start-4 row-span-3 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <livewire:tv.group-match :group-number="2" wire:poll.3s />
            </section>
            <section
                class="tv-panel-leaderboard col-start-3 row-span-4 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <livewire:tv.leaderboard wire:poll.3s />
            </section>
            <section
                class="tv-panel-latest col-start-3 row-start-5 row-span-2 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <livewire:tv.latest-games wire:poll.3s />
            </section>
        </div>
    </div>

    @livewireScripts
</body>

</html>
