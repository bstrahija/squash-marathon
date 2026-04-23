<!doctype html>
<html lang="hr" style="background: transparent">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Overlay - Grupa {{ $groupNumber }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}" />
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}" />
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}" />
    @vite(['resources/css/app.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
    <style>
        html,
        body {
            background: transparent !important;
        }
    </style>
</head>

<body class="overflow-hidden"
    style="background: transparent !important; width: 100vw; height: 100vh; margin: 0; padding: 0;">
    <livewire:overlay.group-match :group-number="$groupNumber" :key="'overlay-group-' . $groupNumber" />
    @livewireScripts
</body>

</html>
