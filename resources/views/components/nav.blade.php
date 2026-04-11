@php
    $navigationLinks = config('navigation.public.links', []);
    $ctaLink = config('navigation.public.cta');

    $resolveNavigationHref = static function (array $item): string {
        $href = route($item['route']);
        $fragment = $item['fragment'] ?? null;

        if (filled($fragment)) {
            $href .= '#' . ltrim((string) $fragment, '#');
        }

        return $href;
    };
@endphp

<nav class="fixed inset-x-0 top-0 z-40 border-b border-border/70 bg-background/80 backdrop-blur">
    <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-5">
        <a class="flex items-center gap-3" href="{{ route('home') }}">
            <img src="{{ asset('images/arena.jpg') }}" alt="Squash Arena Čakovec"
                class="h-12 w-12 rounded-xl border border-border/70 bg-white/90 object-cover shadow-sm dark:bg-white/10"
                loading="lazy" decoding="async" />
            <div>
                <p class="text-sm font-semibold text-foreground">Squash Marathon</p>
                <p class="hidden text-xs text-muted-foreground sm:block">24 sata u pokretu</p>
            </div>
        </a>
        <div class="hidden items-center gap-6 text-sm md:flex">
            @foreach ($navigationLinks as $link)
                <a class="text-muted-foreground transition hover:text-foreground"
                    href="{{ $resolveNavigationHref($link) }}">{{ $link['label'] }}</a>
            @endforeach

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-muted-foreground transition hover:text-foreground">
                        Odjava
                    </button>
                </form>
            @else
                <a class="text-muted-foreground transition hover:text-foreground" href="{{ route('login') }}">
                    Prijava
                </a>
            @endauth
        </div>
        <div class="flex items-center gap-3">
            <button aria-label="Toggle theme" aria-pressed="false"
                class="relative flex h-10 w-10 items-center justify-center rounded-full border border-border bg-card text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40"
                data-theme-toggle title="Toggle theme" type="button">
                <x-heroicon-o-sun aria-hidden="true"
                    class="absolute inset-0 m-auto h-4 w-4 scale-100 opacity-100 transition duration-300"
                    data-theme-icon="sun" />
                <x-heroicon-o-moon aria-hidden="true"
                    class="absolute inset-0 m-auto h-4 w-4 scale-75 opacity-0 transition duration-300"
                    data-theme-icon="moon" />
            </button>

            @if (filled($ctaLink))
                <a class="rounded-full border border-border bg-card px-4 py-2 text-xs font-semibold uppercase tracking-wide text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40"
                    href="{{ $resolveNavigationHref($ctaLink) }}">
                    {{ $ctaLink['label'] }}
                </a>
            @endif

            <button aria-controls="public-nav-panel" aria-expanded="false" aria-label="Open navigation menu"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-border bg-card text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40 md:hidden"
                data-nav-toggle type="button">
                <x-heroicon-o-bars-3 aria-hidden="true" class="h-4 w-4" />
            </button>
        </div>
    </div>

    <div class="hidden border-t border-border/70 bg-background/95 md:hidden" data-nav-panel id="public-nav-panel">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-6 py-4">
            @foreach ($navigationLinks as $link)
                <a class="rounded-xl border border-transparent px-3 py-2 text-sm text-foreground transition hover:border-border hover:bg-card"
                    data-nav-link href="{{ $resolveNavigationHref($link) }}">{{ $link['label'] }}</a>
            @endforeach

            @if (filled($ctaLink))
                <a class="mt-2 rounded-xl border border-border bg-card px-3 py-2 text-sm font-semibold uppercase tracking-wide text-foreground"
                    data-nav-link href="{{ $resolveNavigationHref($ctaLink) }}">
                    {{ $ctaLink['label'] }}
                </a>
            @endif

            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="w-full rounded-xl border border-border bg-card px-3 py-2 text-left text-sm font-semibold uppercase tracking-wide text-foreground"
                        data-nav-link>
                        Odjava
                    </button>
                </form>
            @else
                <a class="mt-2 rounded-xl border border-border bg-card px-3 py-2 text-sm font-semibold uppercase tracking-wide text-foreground"
                    data-nav-link href="{{ route('login') }}">
                    Prijava
                </a>
            @endauth
        </div>
    </div>
</nav>
