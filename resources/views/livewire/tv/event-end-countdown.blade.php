<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function status(): array
    {
        $event = Event::query()->latest('id')->first();

        if (!$event) {
            return [
                'has_event' => false,
                'name' => null,
                'ends_at' => null,
                'remaining_label' => '—',
                'is_over' => false,
            ];
        }

        $now = now();
        $endsAt = $event->end_at;
        $secondsRemaining = $endsAt ? max(0, $now->diffInSeconds($endsAt, false)) : null;

        return [
            'has_event' => true,
            'name' => $event->name,
            'ends_at' => $endsAt,
            'remaining_label' => $secondsRemaining !== null ? $this->formatDuration($secondsRemaining) : '—',
            'is_over' => $endsAt ? $now->greaterThanOrEqualTo($endsAt) : false,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
};
?>

<div class="tv-event-end-countdown flex h-full min-h-0 flex-col p-4" wire:poll.5s>
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
        Event End Countdown
    </p>

    @if (!$this->status['has_event'])
        <p class="mt-3 text-sm font-semibold text-muted-foreground">
            No active event.
        </p>
    @else
        <p class="mt-2 truncate text-base font-semibold text-foreground">
            {{ $this->status['name'] }}
        </p>

        <p class="font-display mt-3 text-4xl font-semibold leading-none text-foreground">
            {{ $this->status['is_over'] ? '00:00:00' : $this->status['remaining_label'] }}
        </p>

        <p class="mt-2 text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground">
            Ends at {{ $this->status['ends_at']?->format('H:i') ?? '—' }}
        </p>
    @endif
</div>
