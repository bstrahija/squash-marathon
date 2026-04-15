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

<body class="bg-background overflow-hidden text-foreground tv-body">
    <div id="tv-group-container"
        class="bg-background p-2 lg:p-4 md:px-6 md:py-6 w-screen h-screen overflow-hidden text-foreground tv-container">
        <section class="bg-card shadow-sm border border-border rounded-3xl h-full min-h-0">
            <div class="bg-card/70 shadow-sm border border-border rounded-3xl h-full min-h-0">
                <livewire:tv.group-match :group-number="$groupNumber" :key="'tv-group-fullscreen-' . $groupNumber" />
            </div>
        </section>
    </div>

    @livewireScripts
</body>

</html>
