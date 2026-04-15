<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TV prikaz - Grupa {{ $groupNumber }}</title>
    @vite(['resources/css/app.css', 'resources/css/tv.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="tv-body overflow-hidden bg-background text-foreground">
    <div id="tv-group-container"
        class="tv-container h-screen w-screen overflow-hidden bg-background p-2 text-foreground md:px-6 md:py-6 lg:p-4">
        <section class="h-full min-h-0 rounded-3xl border border-border bg-card shadow-sm">
            <div class="h-full min-h-0 rounded-3xl border border-border bg-card/70 shadow-sm">
                <livewire:tv.group-match :group-number="$groupNumber" :key="'tv-group-fullscreen-'.$groupNumber" />
            </div>
        </section>
    </div>

    @livewireScripts
</body>

</html>
