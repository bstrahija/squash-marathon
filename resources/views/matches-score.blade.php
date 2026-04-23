<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bodovanje meča</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}" />
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}" />
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="bg-background text-foreground">
    <div class="min-h-screen w-full">
        <livewire:matches-score :game-id="$game" />
    </div>

    @livewireScripts
</body>

</html>
