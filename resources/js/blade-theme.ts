import confetti from 'canvas-confetti';

const root = document.documentElement;

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const readAppearanceCookie = (): string | null => {
    const match = document.cookie.match(/(?:^|;\s*)appearance=([^;]+)/);

    if (!match) {
        return null;
    }

    const value = decodeURIComponent(match[1]);

    if (value === 'light' || value === 'dark' || value === 'system') {
        return value;
    }

    return null;
};

const getStoredTheme = (): string | null => {
    try {
        const value = localStorage.getItem('theme');

        if (value === 'light' || value === 'dark') {
            return value;
        }
    } catch {
        // Ignore localStorage access errors (private mode / blocked storage).
    }

    const cookieAppearance = readAppearanceCookie();

    if (cookieAppearance === 'light' || cookieAppearance === 'dark') {
        return cookieAppearance;
    }

    return null;
};

const persistAppearance = (theme: 'light' | 'dark'): void => {
    try {
        localStorage.setItem('theme', theme);
    } catch {
        // Ignore localStorage access errors.
    }

    document.cookie = `appearance=${theme}; path=/; max-age=31536000; samesite=lax`;
};

const shouldUseDarkTheme = (): boolean => {
    const storedTheme = getStoredTheme();

    return storedTheme === 'dark' || (!storedTheme && mediaQuery.matches);
};

const applyTheme = (): void => {
    root.classList.toggle('dark', shouldUseDarkTheme());
};

const updateThemeToggleUI = (): void => {
    const isDark = root.classList.contains('dark');
    const toggle = document.querySelector<HTMLElement>('[data-theme-toggle]');
    const iconSun = document.querySelector<HTMLElement>(
        '[data-theme-icon="sun"]',
    );
    const iconMoon = document.querySelector<HTMLElement>(
        '[data-theme-icon="moon"]',
    );

    if (toggle) {
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.setAttribute(
            'aria-label',
            isDark ? 'Prebaci na svijetlo' : 'Prebaci na tamno',
        );
        toggle.setAttribute(
            'title',
            isDark ? 'Prebaci na svijetlo' : 'Prebaci na tamno',
        );
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

const initializeThemeToggle = (): void => {
    applyTheme();
    updateThemeToggleUI();

    const toggle = document.querySelector<HTMLElement>('[data-theme-toggle]');

    if (toggle && toggle.dataset.themeToggleBound !== 'true') {
        toggle.dataset.themeToggleBound = 'true';

        toggle.addEventListener('click', () => {
            root.classList.toggle('dark');

            const theme = root.classList.contains('dark') ? 'dark' : 'light';
            persistAppearance(theme);

            updateThemeToggleUI();
        });
    }

    mediaQuery.addEventListener('change', () => {
        if (getStoredTheme()) {
            return;
        }

        applyTheme();
        updateThemeToggleUI();
    });
};

const initializeMobileNavigation = (): void => {
    const toggle = document.querySelector<HTMLButtonElement>('[data-nav-toggle]');
    const panel = document.querySelector<HTMLElement>('[data-nav-panel]');

    if (!toggle || !panel || toggle.dataset.navToggleBound === 'true') {
        return;
    }

    const closeNavigation = (): void => {
        toggle.setAttribute('aria-expanded', 'false');
        panel.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const openNavigation = (): void => {
        toggle.setAttribute('aria-expanded', 'true');
        panel.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    };

    const toggleNavigation = (): void => {
        if (toggle.getAttribute('aria-expanded') === 'true') {
            closeNavigation();

            return;
        }

        openNavigation();
    };

    toggle.dataset.navToggleBound = 'true';
    closeNavigation();

    toggle.addEventListener('click', toggleNavigation);

    panel.querySelectorAll<HTMLElement>('[data-nav-link]').forEach((link) => {
        link.addEventListener('click', closeNavigation);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeNavigation();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            closeNavigation();
        }
    });
};

const registerMatchDoneConfetti = (): void => {
    let matchDoneConfettiInterval: number | null = null;

    const stopConfetti = (): void => {
        if (matchDoneConfettiInterval === null) {
            return;
        }

        window.clearInterval(matchDoneConfettiInterval);
        matchDoneConfettiInterval = null;
    };

    window.launchMatchDoneConfetti = (): void => {
        if (matchDoneConfettiInterval !== null) {
            return;
        }

        const hasOverlay = (): boolean => {
            return (
                document.querySelector('[data-match-done-overlay="true"]') !==
                null
            );
        };

        if (!hasOverlay()) {
            stopConfetti();

            return;
        }

        const defaults: confetti.Options = {
            startVelocity: 34,
            spread: 360,
            ticks: 80,
            zIndex: 80,
            scalar: 1.25,
            colors: [
                '#22c55e',
                '#06b6d4',
                '#f59e0b',
                '#ef4444',
                '#3b82f6',
                '#f43f5e',
            ],
        };

        matchDoneConfettiInterval = window.setInterval((): void => {
            if (!hasOverlay()) {
                stopConfetti();

                return;
            }

            const particleCount = Math.floor(Math.random() * 10) + 20;

            confetti({
                ...defaults,
                particleCount,
                origin: {
                    x: Math.random() * 0.3 + 0.1,
                    y: Math.random() * 0.18 + 0.02,
                },
            });

            confetti({
                ...defaults,
                particleCount,
                origin: {
                    x: Math.random() * 0.3 + 0.6,
                    y: Math.random() * 0.18 + 0.02,
                },
            });
        }, 280);
    };
};

initializeThemeToggle();
initializeMobileNavigation();
registerMatchDoneConfetti();
