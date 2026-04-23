<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bodovanje meča</title>
    @if (config('services.umami.website_id'))
        <script defer src="https://cloud.umami.is/script.js" data-website-id="{{ config('services.umami.website_id') }}">
        </script>
    @endif
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="bg-background text-foreground">
    <div class="w-full min-h-screen">
        <livewire:matches-score :game-id="$game" />
    </div>

    @livewireScripts
</body>

</html>
