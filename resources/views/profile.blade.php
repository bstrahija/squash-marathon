<x-layouts.public-page title="Squash Marathon - Profil"
    main-class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-6 pb-12 pt-32">
    <x-slot:background>
        <div
            class="-top-16 -left-32 absolute bg-emerald-400/30 dark:bg-emerald-500/15 blur-3xl rounded-full w-104 h-104 pointer-events-none">
        </div>
        <div
            class="top-10 -right-24 absolute bg-amber-300/35 dark:bg-amber-400/15 blur-3xl rounded-full w-88 h-88 pointer-events-none">
        </div>
        <div
            class="top-88 left-1/2 absolute bg-sky-300/25 dark:bg-sky-400/10 blur-3xl rounded-full w-[18rem] h-72 -translate-x-1/2 pointer-events-none">
        </div>
    </x-slot:background>

    <section class="scroll-mt-24">
        <div class="flex justify-between items-center gap-4 m-6">
            <h1 class="font-display font-semibold text-foreground text-3xl">Profil</h1>
        </div>

        <div class="bg-card/90 shadow-sm p-6 sm:p-8 border border-border rounded-3xl">
            <p class="mb-6 text-muted-foreground text-sm">Ažurirajte svoje osnovne podatke i lozinku.</p>

            @if (session('status'))
                <div
                    class="bg-emerald-400/10 mb-5 px-4 py-3 border border-emerald-400/40 rounded-2xl text-emerald-700 dark:text-emerald-300 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @php
                $user = auth()->user();
            @endphp

            @if ($user?->hasMedia('avatar'))
                <form id="avatar-remove-form" method="POST" action="{{ route('profile.avatar.destroy') }}"
                    class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="items-center gap-4 grid sm:grid-cols-[auto,1fr]">
                    <div class="relative mx-auto sm:mx-0">
                        <img src="{{ $user?->avatarUrl('thumb') }}" alt="Avatar korisnika"
                            class="border border-border/70 rounded-2xl w-20 h-20 object-cover" loading="lazy"
                            decoding="async" />

                        @if ($user?->hasMedia('avatar'))
                            <button type="submit" form="avatar-remove-form" title="Ukloni avatar"
                                aria-label="Ukloni avatar"
                                class="inline-flex -top-1 -left-1 absolute justify-center items-center bg-rose-500/95 hover:bg-rose-500 border border-rose-400/80 rounded-full w-5 h-5 text-white transition">
                                <x-heroicon-o-x-mark class="w-3 h-3" />
                            </button>
                        @endif
                    </div>

                    <label class="block">
                        <span
                            class="block mb-1.5 font-semibold text-muted-foreground text-xs uppercase tracking-[0.14em]">
                            Avatar
                        </span>
                        <input type="file" name="avatar" accept="image/*"
                            class="block bg-background/70 file:bg-card px-3 file:px-3 py-2 file:py-2.5 border file:border border-border/70 file:border-border/70 rounded-xl file:rounded-xl w-full file:font-semibold text-foreground file:text-foreground file:text-xs text-sm file:uppercase file:tracking-wide file:cursor-pointer" />
                        <p class="mt-1.5 text-muted-foreground text-xs">Dozvoljene su slike do 1 MB.</p>
                        @error('avatar')
                            <p class="mt-1.5 text-rose-600 dark:text-rose-300 text-xs">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div class="gap-4 grid sm:grid-cols-2">
                    <label class="block">
                        <span
                            class="block mb-1.5 font-semibold text-muted-foreground text-xs uppercase tracking-[0.14em]">
                            Ime
                        </span>
                        <input type="text" name="first_name" value="{{ old('first_name', $user?->first_name) }}"
                            autocomplete="given-name"
                            class="bg-background/70 px-3 py-2.5 border border-border/70 focus:border-foreground/40 rounded-xl outline-none w-full text-foreground text-sm transition" />
                        @error('first_name')
                            <p class="mt-1.5 text-rose-600 dark:text-rose-300 text-xs">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span
                            class="block mb-1.5 font-semibold text-muted-foreground text-xs uppercase tracking-[0.14em]">
                            Prezime
                        </span>
                        <input type="text" name="last_name" value="{{ old('last_name', $user?->last_name) }}"
                            autocomplete="family-name"
                            class="bg-background/70 px-3 py-2.5 border border-border/70 focus:border-foreground/40 rounded-xl outline-none w-full text-foreground text-sm transition" />
                        @error('last_name')
                            <p class="mt-1.5 text-rose-600 dark:text-rose-300 text-xs">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div class="gap-4 grid sm:grid-cols-2">
                    <label class="block">
                        <span
                            class="block mb-1.5 font-semibold text-muted-foreground text-xs uppercase tracking-[0.14em]">
                            Nova lozinka
                        </span>
                        <input type="password" name="password" autocomplete="new-password"
                            class="bg-background/70 px-3 py-2.5 border border-border/70 focus:border-foreground/40 rounded-xl outline-none w-full text-foreground text-sm transition" />
                        @error('password')
                            <p class="mt-1.5 text-rose-600 dark:text-rose-300 text-xs">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span
                            class="block mb-1.5 font-semibold text-muted-foreground text-xs uppercase tracking-[0.14em]">
                            Potvrda lozinke
                        </span>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            class="bg-background/70 px-3 py-2.5 border border-border/70 focus:border-foreground/40 rounded-xl outline-none w-full text-foreground text-sm transition" />
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="inline-flex justify-center items-center bg-card px-5 py-2.5 border border-border hover:border-foreground/40 rounded-full font-semibold text-foreground text-xs uppercase tracking-wide transition hover:-translate-y-0.5">
                        Spremi promjene
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-layouts.public-page>
