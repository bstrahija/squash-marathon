<!doctype html>
<html lang="hr">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bodovanje meča</title>
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
