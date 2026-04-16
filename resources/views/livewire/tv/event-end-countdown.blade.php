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
                'ends_at' => null,
                'ends_at_unix' => null,
                'remaining_seconds' => null,
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
            'ends_at_unix' => $endsAt?->getTimestamp(),
            'remaining_seconds' => $secondsRemaining,
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

<div class="tv-event-end-countdown flex h-full min-h-0 flex-col p-4" wire:poll.5s
     data-remaining-seconds="{{ $this->status['remaining_seconds'] ?? '' }}"
     data-ends-at-unix="{{ $this->status['ends_at_unix'] ?? '' }}"
     data-is-over="{{ $this->status['is_over'] ? '1' : '0' }}" x-data="{
         targetEpoch: null,
         isOver: false,
         displayLabel: '{{ $this->status['is_over'] ? '00:00:00' : $this->status['remaining_label'] }}',
         formatDuration(seconds) {
             const total = Math.max(0, Number(seconds) || 0);
             const hours = Math.floor(total / 3600);
             const minutes = Math.floor((total % 3600) / 60);
             const secs = total % 60;
     
             return [hours, minutes, secs].map((value) => String(value).padStart(2, '0')).join(':');
         },
         syncFromServer() {
             const rawTargetEpoch = this.$el.dataset.endsAtUnix;
             const nextTargetEpoch = rawTargetEpoch === '' ? null : Number(rawTargetEpoch);
     
             this.targetEpoch = Number.isNaN(nextTargetEpoch) ? null : nextTargetEpoch;
             this.isOver = this.$el.dataset.isOver === '1';
     
             if (this.targetEpoch === null) {
                 this.displayLabel = '—';
     
                 return;
             }
     
             this.tick();
         },
         tick() {
             if (this.targetEpoch === null) {
                 return;
             }
     
             const nowEpoch = Math.floor(Date.now() / 1000);
             const remainingSeconds = Math.max(0, this.targetEpoch - nowEpoch);
     
             if (this.isOver || remainingSeconds === 0) {
                 this.isOver = true;
                 this.displayLabel = '00:00:00';
     
                 return;
             }
     
             this.displayLabel = this.formatDuration(remainingSeconds);
         },
         init() {
             this.syncFromServer();
     
             if (this.$el._tvCountdownTimer) {
                 window.clearInterval(this.$el._tvCountdownTimer);
             }
     
             if (this.$el._tvCountdownObserver) {
                 this.$el._tvCountdownObserver.disconnect();
             }
     
             this.$el._tvCountdownTimer = window.setInterval(() => this.tick(), 1000);
             this.$el._tvCountdownObserver = new MutationObserver(() => this.syncFromServer());
             this.$el._tvCountdownObserver.observe(this.$el, {
                 attributes: true,
                 attributeFilter: ['data-ends-at-unix', 'data-is-over', 'data-remaining-seconds'],
             });
         }
     }" x-init="init()">
    <p class="text-muted-foreground tv-event-kicker font-semibold uppercase tracking-[0.18em]">
        Event End Countdown
    </p>

    @if (!$this->status['has_event'])
        <p class="text-muted-foreground tv-event-name mt-3 font-semibold">
            No active event.
        </p>
    @else
        <p class="text-foreground tv-event-name mt-2 truncate font-semibold">
            {{ $this->status['name'] }}
        </p>

        <p class="font-display text-foreground tv-event-timer mt-3 font-semibold leading-none" x-text="displayLabel">
            {{ $this->status['is_over'] ? '00:00:00' : $this->status['remaining_label'] }}
        </p>

        <p class="text-muted-foreground tv-event-meta mt-2 font-semibold uppercase tracking-[0.14em]">
            Ends at {{ $this->status['ends_at']?->format('H:i') ?? '—' }}
        </p>
    @endif
</div>
