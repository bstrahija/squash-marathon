@props([
    'initials' => null,
])

<div
    class="font-initials flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-xs font-semibold text-primary-foreground">
    {{ filled($initials) ? $initials : '—' }}
</div>
