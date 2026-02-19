<?php

use Livewire\Component;

new class extends Component {
    public string $eventName = 'Squash Marathon';
    public string $status = 'Planning';
};
?>

<div class="rounded-lg border border-border bg-card p-6">
    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Event Status</p>
    <h2 class="mt-2 text-2xl font-semibold text-foreground">{{ $eventName }}</h2>
    <p class="mt-1 text-sm text-muted-foreground">Status: {{ $status }}</p>
    <p class="mt-4 text-sm text-foreground">24-hour continuous play window.</p>
</div>
