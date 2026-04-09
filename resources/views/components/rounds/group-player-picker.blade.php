@props([
    'title',
    'groupNumber',
    'players',
    'availablePlayers',
    'addModel',
    'updateMethod',
    'errorKey',
    'errorItemKey',
    'wireKeyPrefix' => 'group-picker',
])

@php($selectId = sprintf('%s-player-to-add', $wireKeyPrefix))

<section class="rounded-2xl border border-border/70 bg-background/70 p-4">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="font-display text-xl font-semibold text-foreground">{{ $title }}</h2>
        <span
            class="rounded-full border border-border px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $players->count() }} igrača
        </span>
    </div>

    <div class="mb-4">
        <label for="{{ $selectId }}"
            class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
            Dodaj igrača
        </label>
        <select id="{{ $selectId }}" wire:model.live="{{ $addModel }}"
            class="w-full rounded-2xl border border-border/70 bg-background/80 px-4 py-3 text-sm text-foreground focus:border-foreground/40 focus:outline-none"
            wire:loading.attr="disabled" wire:target="{{ $updateMethod }},saveRound">
            <option value="">Odaberi igrača</option>
            @foreach ($availablePlayers as $player)
                <option value="{{ $player->id }}">{{ $player->full_name }}</option>
            @endforeach
        </select>
    </div>

    @if ($players->isEmpty())
        <div
            class="rounded-2xl border border-dashed border-border/70 px-4 py-6 text-center text-sm text-muted-foreground">
            Još nema igrača u {{ strtolower($title) }}.
        </div>
    @else
        <ul class="space-y-2">
            @foreach ($players as $player)
                <li wire:key="{{ $wireKeyPrefix }}-player-{{ $player->id }}"
                    class="flex items-center justify-between gap-3 rounded-xl border border-border/70 bg-card px-3 py-2.5 text-sm">
                    <span class="font-medium text-foreground">{{ $player->full_name }}</span>
                    <button type="button"
                        wire:click="removePlayerFromGroup({{ $groupNumber }}, {{ $player->id }})"
                        wire:loading.attr="disabled" wire:target="removePlayerFromGroup,saveRound"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:border-red-400/60 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                        <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    @error($errorKey)
        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
    @error($errorItemKey)
        <p class="mt-2 text-xs text-red-600 dark:text-red-300">{{ $message }}</p>
    @enderror
</section>
