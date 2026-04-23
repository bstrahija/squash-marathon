<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function status(): array
    {
        $event = Event::current();

        if (!$event) {
            return [
                'has_event' => false,
                'name' => null,
                'starts_at' => null,
                'ends_at' => null,
                'is_over' => false,
                'remaining_label' => null,
            ];
        }

        $now = now();
        $endsAt = $event->end_at;
        $secondsRemaining = $endsAt ? (int) round($now->diffInSeconds($endsAt, false)) : null;

        if ($secondsRemaining !== null && $secondsRemaining < 0) {
            $secondsRemaining = 0;
        }

        return [
            'has_event' => true,
            'name' => $event->name,
            'starts_at' => $event->start_at,
            'ends_at' => $endsAt,
            'is_over' => $endsAt ? $now->greaterThanOrEqualTo($endsAt) : false,
            'remaining_label' => $secondsRemaining !== null ? $this->formatDuration($secondsRemaining) : null,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours >= 24) {
            $days = intdiv($hours, 24);
            $hours = $hours % 24;

            return sprintf('%dd %dh %dmin', $days, $hours, $minutes);
        }

        if ($hours > 0) {
            return sprintf('%dh %dmin', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%dmin %ds', $minutes, $remainingSeconds);
        }

        return sprintf('%ds', $remainingSeconds);
    }
};
?>

<div class="border-border bg-card rounded-3xl border p-6 shadow-sm" wire:poll.5s>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">Status događaja</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">
                {{ $this->status['name'] ?? 'Squash maraton' }}
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <button aria-label="Toggle theme" aria-pressed="false"
                class="border-border bg-background/70 text-foreground hover:border-foreground/40 relative flex h-9 w-9 items-center justify-center rounded-full border transition hover:-translate-y-0.5"
                data-theme-toggle title="Toggle theme" type="button">
                <x-heroicon-o-sun aria-hidden="true"
                    class="absolute inset-0 m-auto h-3.5 w-3.5 scale-100 opacity-100 transition duration-300"
                    data-theme-icon="sun" />
                <x-heroicon-o-moon aria-hidden="true"
                    class="absolute inset-0 m-auto h-3.5 w-3.5 scale-75 opacity-0 transition duration-300"
                    data-theme-icon="moon" />
            </button>
            <span
                class="border-border/70 bg-background/70 text-foreground rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]">
                {{ $this->status['is_over'] ? 'Završeno' : 'U tijeku' }}
            </span>
        </div>
    </div>

    @if (!$this->status['has_event'])
        <div
            class="border-border/70 bg-background/70 text-muted-foreground mt-6 rounded-2xl border border-dashed px-4 py-6 text-sm">
            Još nema aktivnog događaja.
        </div>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="border-border/70 bg-background/70 rounded-2xl border p-4">
                <p class="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">Preostalo</p>
                <p class="font-display text-foreground mt-3 text-3xl font-semibold">
                    {{ $this->status['is_over'] ? 'Završeno' : $this->status['remaining_label'] ?? '—' }}
                </p>
            </div>
            <div class="border-border/70 bg-background/70 rounded-2xl border p-4">
                <p class="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">Vrijeme kraja</p>
                <p class="font-display text-foreground mt-3 text-3xl font-semibold">
                    {{ $this->status['ends_at']?->setTimezone(config('app.display_timezone'))->format('H:i') ?? '—' }}
                </p>
                <p class="text-muted-foreground mt-1 text-xs">
                    {{ $this->status['ends_at']?->setTimezone(config('app.display_timezone'))->format('d.m.Y') ?? '—' }}
                </p>
            </div>
        </div>
    @endif

    <p class="text-muted-foreground mt-3 text-[11px]">
        Automatsko osvježavanje svakih {{ config('polling.components.event_countdown') }} sekundi
    </p>
</div>
