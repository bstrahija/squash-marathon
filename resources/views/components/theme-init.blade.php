@php
    $appearanceSetting = in_array($appearance ?? 'system', ['light', 'dark', 'system'], true)
        ? ($appearance ?? 'system')
        : 'system';
@endphp

<script>
    (() => {
        const root = document.documentElement;

        const readCookieAppearance = () => {
            const match = document.cookie.match(/(?:^|;\s*)appearance=([^;]+)/);

            if (!match) {
                return null;
            }

            const value = decodeURIComponent(match[1]);

            return value === 'light' || value === 'dark' || value === 'system'
                ? value
                : null;
        };

        const readLocalTheme = () => {
            try {
                const value = window.localStorage.getItem('theme');

                return value === 'light' || value === 'dark' ? value : null;
            } catch {
                return null;
            }
        };

        const serverAppearance = @js($appearanceSetting);
        const localTheme = readLocalTheme();
        const cookieAppearance = readCookieAppearance();
        const appearance = localTheme ?? cookieAppearance ?? serverAppearance;

        const useDark = appearance === 'dark' ||
            (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        root.classList.toggle('dark', useDark);
    })();
</script>
