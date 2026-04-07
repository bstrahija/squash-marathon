const root = document.documentElement;

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const getStoredTheme = (): string | null => {
    return localStorage.getItem('theme');
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
            localStorage.setItem('theme', theme);

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

initializeThemeToggle();
