<!doctype html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Squash maraton - praćenje</title>
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-background text-foreground">
    <div class="relative overflow-hidden">
        <div
            class="pointer-events-none absolute -left-32 top-[-4rem] h-[26rem] w-[26rem] rounded-full bg-emerald-400/30 blur-3xl dark:bg-emerald-500/15">
        </div>
        <div
            class="pointer-events-none absolute right-[-6rem] top-10 h-[22rem] w-[22rem] rounded-full bg-amber-300/35 blur-3xl dark:bg-amber-400/15">
        </div>
        <div
            class="pointer-events-none absolute left-1/2 top-[22rem] h-[18rem] w-[18rem] -translate-x-1/2 rounded-full bg-sky-300/25 blur-3xl dark:bg-sky-400/10">
        </div>
        <div
            class="pointer-events-none absolute left-8 top-[18rem] hidden h-64 w-64 rotate-6 rounded-[2.5rem] border border-emerald-400/30 bg-emerald-400/5 dark:border-emerald-400/10 dark:bg-emerald-500/5 lg:block">
        </div>

        <x-nav />

        <main class="mx-auto flex w-full max-w-6xl flex-col gap-16 px-6 pb-12 pt-32">
            <x-hero />

            <section id="about" class="scroll-mt-24">
                <x-event-info />
            </section>

            <section id="scoring" class="scroll-mt-24">
                <x-scoring />
            </section>

            <section id="participants" class="scroll-mt-24">
                <x-participants :participants="$participants" />
            </section>

            <section id="leaderboard" class="scroll-mt-24">
                <x-leaderboard :leaderboard="$leaderboard" />
            </section>

            <section id="timeline" class="scroll-mt-24">
                <x-timeline :timeline="$timeline" />
            </section>
        </main>

        <x-footer />
    </div>

    <script>
        const root = document.documentElement;
        const storedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
            root.classList.add('dark');
        }

        const toggle = document.querySelector('[data-theme-toggle]');
        const iconSun = document.querySelector('[data-theme-icon="sun"]');
        const iconMoon = document.querySelector('[data-theme-icon="moon"]');

        const updateLabel = () => {
            const isDark = root.classList.contains('dark');
            if (toggle) {
                toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            }
            if (toggle) {
                toggle.setAttribute('aria-label', isDark ? 'Prebaci na svijetlo' : 'Prebaci na tamno');
                toggle.setAttribute('title', isDark ? 'Prebaci na svijetlo' : 'Prebaci na tamno');
            }
            if (iconSun) {
                iconSun.classList.toggle('opacity-0', !isDark);
                iconSun.classList.toggle('scale-75', !isDark);
                iconSun.classList.toggle('opacity-100', isDark);
                iconSun.classList.toggle('scale-100', isDark);
            }
            if (iconMoon) {
                iconMoon.classList.toggle('opacity-0', isDark);
                iconMoon.classList.toggle('scale-75', isDark);
                iconMoon.classList.toggle('opacity-100', !isDark);
                iconMoon.classList.toggle('scale-100', !isDark);
            }
        };

        if (toggle) {
            toggle.addEventListener('click', () => {
                root.classList.toggle('dark');
                localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
                updateLabel();
            });
        }

        updateLabel();
    </script>
</body>

</html>
