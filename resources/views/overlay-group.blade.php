<!doctype html>
<html lang="hr" style="background: transparent">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Overlay - Grupa {{ $groupNumber }}</title>
    @vite(['resources/css/app.css', 'resources/js/blade-theme.ts'])
    @livewireStyles
    <style>
        html,
        body {
            background: transparent !important;
        }
    </style>
</head>

<body class="overflow-hidden" style="background: transparent !important; width: 100vw; height: 100vh; margin: 0; padding: 0;">
    <livewire:overlay.group-match :group-number="$groupNumber" :key="'overlay-group-' . $groupNumber" />
    @livewireScripts
</body>

</html>
