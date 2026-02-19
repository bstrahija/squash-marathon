<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Squash Marathon Tracker</title>
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>

<body class="min-h-screen bg-background text-foreground">
    <main class="mx-auto w-full max-w-5xl px-6 py-12">
        <header>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                24-hour squash event
            </p>
            <h1 class="mt-3 text-4xl font-semibold">Squash Marathon Tracker</h1>
            <p class="mt-4 max-w-2xl text-sm text-muted-foreground">
                Track participants, matches, and courts during a continuous 24-hour squash
                marathon.
            </p>
        </header>

        <section class="mt-8">
            <livewire:homepage-status />
        </section>
    </main>

    @livewireScripts
</body>

</html>
