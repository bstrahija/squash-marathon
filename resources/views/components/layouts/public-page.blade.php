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
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}" />
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}" />
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
</head>

<body class="min-h-svh bg-background text-foreground">
    <div class="relative flex min-h-svh flex-col overflow-hidden">
        {{ $background ?? '' }}

        <x-nav />

        <main class="flex-1 {{ $mainClass }}">
            {{ $slot }}
        </main>

        <x-footer />
    </div>

    @livewireScripts
</body>

</html>
