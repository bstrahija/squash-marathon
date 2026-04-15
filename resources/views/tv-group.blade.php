<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>TV prikaz - Grupa {{ $groupNumber }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}" />
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}" />
    @vite(['resources/css/app.css', 'resources/css/tv.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="tv-body overflow-hidden bg-background text-foreground">
    <div class="tv-home-header">
        <x-nav />
    </div>

    <div id="tv-group-container"
        class="tv-container h-screen w-screen overflow-hidden bg-background p-2 text-foreground md:px-6 md:py-6 lg:p-4">
        <section class="h-full min-h-0 rounded-3xl border border-border bg-card shadow-sm">
            <div class="h-full min-h-0 rounded-3xl border border-border bg-card/70 shadow-sm">
                <livewire:tv.group-match :group-number="$groupNumber" :key="'tv-group-fullscreen-' . $groupNumber" />
                <livewire:tv.group-match :group-number="$groupNumber" :key="'tv-group-fullscreen-' . $groupNumber" />
            </div>
        </section>
    </div>

    @livewireScripts
</body>

</html>
