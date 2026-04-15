@props([
    'player' => [],
])

@php
    $avatarUrl = $player['avatar_url'] ?? null;
    $initials = $player['initials'] ?? null;
    $name = $player['name'] ?? 'Player avatar';
@endphp

@if (filled($avatarUrl))
    <img src="{{ $avatarUrl }}" alt="{{ $name }}" class="h-10 w-10 rounded-xl object-cover" loading="lazy"
        decoding="async" />
@else
    <div
        class="font-initials flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-xs font-semibold text-primary-foreground">
        {{ filled($initials) ? $initials : '—' }}
    </div>
@endif
