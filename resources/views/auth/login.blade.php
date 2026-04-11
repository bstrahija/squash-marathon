<x-layouts.public-page title="Squash Marathon - Prijava"
    main-class="mx-auto flex w-full max-w-xl flex-col gap-8 px-6 pb-12 pt-32">
    <section class="scroll-mt-24">
        <div class="rounded-3xl border border-border bg-card/80 p-6 shadow-sm sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Prijava</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-foreground">Prijavite se</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Za upravljanje mečevima prijavite se svojim korisničkim računom.
            </p>

            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        Email
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none" />
                </div>

                <div>
                    <label for="password" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        Lozinka
                    </label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-2xl border border-border/70 bg-background/70 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none" />
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                    <input type="checkbox" name="remember" value="1"
                        class="h-4 w-4 rounded border-border bg-background text-foreground focus:ring-0" />
                    Zapamti me
                </label>

                <button type="submit"
                    class="w-full rounded-full bg-primary px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-primary-foreground shadow-sm transition hover:-translate-y-0.5">
                    Prijava
                </button>
            </form>

            <div class="mt-6 flex items-center gap-3">
                <div class="h-px flex-1 bg-border/70"></div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">ili</span>
                <div class="h-px flex-1 bg-border/70"></div>
            </div>

            <a href="{{ route('oauth.google.redirect') }}"
                class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full border border-border bg-card px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-foreground transition hover:-translate-y-0.5 hover:border-foreground/40">
                <x-icons.google class="h-4 w-4" aria-hidden="true" />
                Nastavi s Google
            </a>
        </div>
    </section>
</x-layouts.public-page>
