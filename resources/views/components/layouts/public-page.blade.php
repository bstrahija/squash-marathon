@props([
    'title' => config('app.name'),
    'mainClass' => 'mx-auto flex w-full max-w-6xl flex-col gap-16 px-6 pb-12 pt-32',
])

<!doctype html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="min-h-screen bg-background text-foreground">
    <div class="relative overflow-hidden">
        {{ $background ?? '' }}

        <x-nav />

        <main class="{{ $mainClass }}">
            {{ $slot }}
        </main>

        <x-footer />
    </div>

    @livewireScripts
</body>

</html>
