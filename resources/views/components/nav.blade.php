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

<nav class="top-0 z-40 fixed inset-x-0 bg-background/80 backdrop-blur border-border/70 border-b">
    <div class="flex justify-between items-center mx-auto px-6 py-5 w-full max-w-6xl">
        <a class="flex items-center gap-3" href="{{ route('home') }}">
            <img src="{{ asset('images/arena.jpg') }}" alt="Squash Arena Čakovec"
                class="bg-white/90 dark:bg-white/10 shadow-sm border border-border/70 rounded-xl w-12 h-12 object-cover"
                loading="lazy" decoding="async" />
            <div>
                <p class="font-semibold text-foreground text-sm">Squash Marathon</p>
                <p class="hidden sm:block text-muted-foreground text-xs">24 sata u pokretu</p>
            </div>
        </a>
        <div class="hidden md:flex items-center gap-6 text-sm">
            @foreach ($navigationLinks as $link)
                <a class="text-muted-foreground hover:text-foreground transition"
                    href="{{ $resolveNavigationHref($link) }}">{{ $link['label'] }}</a>
            @endforeach

            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-muted-foreground hover:text-foreground transition">
                        Odjava
                    </button>
                </form>
            @else
                <a class="text-muted-foreground hover:text-foreground transition" href="{{ route('login') }}">
                    Prijava
                </a>
            @endauth
        </div>
        <div class="flex items-center gap-3">
            <button aria-label="Toggle theme" aria-pressed="false"
                class="relative flex justify-center items-center bg-card border border-border hover:border-foreground/40 rounded-full w-10 h-10 text-foreground transition hover:-translate-y-0.5"
                data-theme-toggle title="Toggle theme" type="button">
                <x-heroicon-o-sun aria-hidden="true"
                    class="absolute inset-0 opacity-100 m-auto w-4 h-4 scale-100 transition duration-300"
                    data-theme-icon="sun" />
                <x-heroicon-o-moon aria-hidden="true"
                    class="absolute inset-0 opacity-0 m-auto w-4 h-4 scale-75 transition duration-300"
                    data-theme-icon="moon" />
            </button>

            @if (filled($ctaLink))
                <a class="bg-card px-4 py-2 border border-border hover:border-foreground/40 rounded-full font-semibold text-foreground text-xs uppercase tracking-wide transition hover:-translate-y-0.5"
                    href="{{ $resolveNavigationHref($ctaLink) }}">
                    {{ $ctaLink['label'] }}
                </a>
            @endif

            <button aria-controls="public-nav-panel" aria-expanded="false" aria-label="Open navigation menu"
                class="md:hidden inline-flex justify-center items-center bg-card border border-border hover:border-foreground/40 rounded-full w-10 h-10 text-foreground transition hover:-translate-y-0.5"
                data-nav-toggle type="button">
                <x-heroicon-o-bars-3 aria-hidden="true" class="w-4 h-4" />
            </button>
        </div>
    </div>

    <div class="hidden md:hidden bg-background/95 border-border/70 border-t" data-nav-panel id="public-nav-panel">
        <div class="flex flex-col gap-2 mx-auto px-6 py-4 w-full max-w-6xl">
            @foreach ($navigationLinks as $link)
                <a class="hover:bg-card px-3 py-2 border border-transparent hover:border-border rounded-xl text-foreground text-sm transition"
                    data-nav-link href="{{ $resolveNavigationHref($link) }}">{{ $link['label'] }}</a>
            @endforeach

            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                        class="bg-card px-3 py-2 border border-border rounded-xl w-full font-semibold text-foreground text-sm text-left uppercase tracking-wide"
                        data-nav-link>
                        Odjava
                    </button>
                </form>
            @else
                <a class="bg-card mt-2 px-3 py-2 border border-border rounded-xl font-semibold text-foreground text-sm uppercase tracking-wide"
                    data-nav-link href="{{ route('login') }}">
                    Prijava
                </a>
            @endauth
        </div>
    </div>
</nav>
