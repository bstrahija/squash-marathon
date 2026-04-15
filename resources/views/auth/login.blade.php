<x-layouts.public-page title="Squash Marathon - Prijava"
    main-class="mx-auto flex w-full max-w-xl flex-col gap-8 px-6 pb-12 pt-32">
    <section class="scroll-mt-24">
        <div class="bg-card/80 shadow-sm p-6 sm:p-8 border border-border rounded-3xl">
            <p class="font-semibold text-muted-foreground text-xs uppercase tracking-[0.2em]">Prijava</p>
            <h1 class="mt-2 font-display font-semibold text-foreground text-3xl">Prijavite se</h1>
            <p class="mt-2 text-muted-foreground text-sm">
                Za upravljanje mečevima prijavite se svojim korisničkim računom.
            </p>

            @if ($errors->any())
                <div
                    class="bg-red-500/10 mt-5 px-4 py-3 border border-red-500/40 rounded-2xl text-red-700 dark:text-red-300 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5 mt-6">
                @csrf

                <div>
                    <label for="email"
                        class="block mb-2 font-semibold text-muted-foreground text-xs uppercase tracking-[0.16em]">
                        Email
                    </label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="bg-background/70 px-4 py-3 border border-border/70 focus:border-foreground/40 rounded-2xl focus:outline-none w-full text-foreground text-sm" />
                </div>

                <div>
                    <label for="password"
                        class="block mb-2 font-semibold text-muted-foreground text-xs uppercase tracking-[0.16em]">
                        Lozinka
                    </label>
                    <input id="password" name="password" type="password" required
                        class="bg-background/70 px-4 py-3 border border-border/70 focus:border-foreground/40 rounded-2xl focus:outline-none w-full text-foreground text-sm" />
                </div>

                <label class="inline-flex items-center gap-2 text-muted-foreground text-sm">
                    <input type="checkbox" name="remember" value="1"
                        class="bg-background border-border rounded focus:ring-0 w-4 h-4 text-foreground" />
                    Zapamti me
                </label>

                <button type="submit"
                    class="bg-primary shadow-sm px-5 py-2.5 rounded-full w-full font-semibold text-primary-foreground text-xs uppercase tracking-wide transition hover:-translate-y-0.5">
                    Prijava
                </button>
            </form>

            <div class="flex items-center gap-3 mt-6">
                <div class="flex-1 bg-border/70 h-px"></div>
                <span class="font-semibold text-[11px] text-muted-foreground uppercase tracking-[0.16em]">ili</span>
                <div class="flex-1 bg-border/70 h-px"></div>
            </div>

            <a href="{{ route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']) }}"
                class="inline-flex justify-center items-center gap-2 bg-card mt-6 px-5 py-2.5 border border-border hover:border-foreground/40 rounded-full w-full font-semibold text-foreground text-xs uppercase tracking-wide transition hover:-translate-y-0.5">
                <x-icons.google class="w-4 h-4" aria-hidden="true" />
                Nastavi s Google
            </a>
        </div>
    </section>
</x-layouts.public-page>
