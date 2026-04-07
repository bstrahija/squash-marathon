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
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>

<body class="bg-background text-foreground overflow-hidden">
    <div id="tv-container" class="h-screen w-screen overflow-hidden bg-background px-6 py-6 text-foreground lg:p-4">
        <div class="grid h-full grid-cols-3 grid-rows-2 gap-4">
            <section class="col-span-2 min-h-0 rounded-3xl border border-border bg-card shadow-sm"></section>
            <section class="col-span-2 row-start-2 min-h-0 rounded-3xl border border-border bg-card shadow-sm"></section>
            <section class="col-start-3 row-start-1 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <livewire:tv.leaderboard />
            </section>
            <section class="col-start-3 row-start-2 min-h-0 rounded-3xl border border-border bg-card shadow-sm">
                <livewire:tv.latest-games />
            </section>
        </div>
    </div>

    @livewireScripts
</body>

</html>
