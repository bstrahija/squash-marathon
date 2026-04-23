<?php

use App\Models\Event;
use Carbon\CarbonInterface;
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
                'is_started' => false,
                'is_over' => false,
                'starts_at' => null,
                'ends_at' => null,
                'ends_at_unix' => null,
                'remaining_seconds' => null,
                'remaining_label' => '—',
                'duration_label' => '—',
            ];
        }

        $now = now();
        $startsAt = $event->start_at;
        $endsAt = $event->end_at;

        $isStarted = $startsAt ? $now->greaterThanOrEqualTo($startsAt) : true;
        $isOver = $endsAt ? $now->greaterThanOrEqualTo($endsAt) : false;
        $secondsRemaining = $endsAt && $isStarted ? max(0, (int) round($now->diffInSeconds($endsAt, false))) : null;

        $durationSeconds = $startsAt && $endsAt ? (int) round($startsAt->diffInSeconds($endsAt)) : null;
        $durationLabel = $durationSeconds !== null ? $this->formatDuration($durationSeconds) : '—';

        return [
            'has_event' => true,
            'name' => $event->name,
            'is_started' => $isStarted,
            'is_over' => $isOver,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'ends_at_unix' => $isStarted && $endsAt ? $endsAt->getTimestamp() : null,
            'remaining_seconds' => $secondsRemaining,
            'remaining_label' => $secondsRemaining !== null ? $this->formatDuration($secondsRemaining) : '—',
            'duration_label' => $durationLabel,
            'starts_at_label' => $isStarted ? null : $this->formatCroatianStartsAt($startsAt),
        ];
    }

    private function formatCroatianStartsAt(?CarbonInterface $startsAt): string
    {
        if (!$startsAt) {
            return '—';
        }

        $startsAt = $startsAt->setTimezone(config('app.display_timezone'));

        $time = $startsAt->format('H:i');

        if ($startsAt->isToday()) {
            return $time;
        }

        $days = [
            0 => 'nedjelja',
            1 => 'ponedjeljak',
            2 => 'utorak',
            3 => 'srijeda',
            4 => 'četvrtak',
            5 => 'petak',
            6 => 'subota',
        ];

        $months = [
            1 => 'siječnja',
            2 => 'veljače',
            3 => 'ožujka',
            4 => 'travnja',
            5 => 'svibnja',
            6 => 'lipnja',
            7 => 'srpnja',
            8 => 'kolovoza',
            9 => 'rujna',
            10 => 'listopada',
            11 => 'studenog',
            12 => 'prosinca',
        ];

        $dayName = $days[$startsAt->dayOfWeek];
        $monthName = $months[$startsAt->month];

        return "{$dayName}, {$startsAt->day}. {$monthName} {$time}";
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

<div class="flex flex-col p-4 h-full min-h-0 tv-event-end-countdown" wire:poll.5s
    data-remaining-seconds="{{ $this->status['remaining_seconds'] ?? '' }}"
    data-ends-at-unix="{{ $this->status['ends_at_unix'] ?? '' }}"
    data-is-over="{{ $this->status['is_over'] ? '1' : '0' }}"
    data-is-started="{{ $this->status['is_started'] ? '1' : '0' }}" x-data="{
        targetEpoch: null,
        isOver: false,
        isStarted: false,
        displayLabel: '{{ $this->status['is_over'] ? '00:00:00' : ($this->status['is_started'] ? $this->status['remaining_label'] : $this->status['duration_label']) }}',
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
            this.isStarted = this.$el.dataset.isStarted === '1';
    
            if (!this.isStarted) {
                return;
            }
    
            if (this.targetEpoch === null) {
                this.displayLabel = '—';
    
                return;
            }
    
            this.tick();
        },
        tick() {
            if (!this.isStarted || this.targetEpoch === null) {
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
                attributeFilter: ['data-ends-at-unix', 'data-is-over', 'data-is-started', 'data-remaining-seconds'],
            });
        }
    }" x-init="init()">
    <p class="font-semibold text-muted-foreground uppercase tracking-[0.18em] tv-event-kicker">
        @if ($this->status['has_event'] && !$this->status['is_started'])
            Countdown do kraja
        @else
            Event End Countdown
        @endif
    </p>

    @if (!$this->status['has_event'])
        <p class="mt-3 font-semibold text-muted-foreground tv-event-name">
            No active event.
        </p>
    @else
        <p class="mt-2 font-semibold text-foreground truncate tv-event-name">
            {{ $this->status['name'] }}
        </p>

        <p class="mt-3 font-display font-semibold text-foreground leading-none tv-event-timer" x-text="displayLabel">
            @if ($this->status['is_over'])
                00:00:00
            @elseif ($this->status['is_started'])
                {{ $this->status['remaining_label'] }}
            @else
                {{ $this->status['duration_label'] }}
            @endif
        </p>

        <p class="mt-2 font-semibold text-muted-foreground uppercase tracking-[0.14em] tv-event-meta">
            @if ($this->status['is_started'])
                Ends at
                {{ $this->status['ends_at']?->setTimezone(config('app.display_timezone'))->format('H:i') ?? '—' }}
            @else
                Počinje {{ $this->status['starts_at_label'] ?? '—' }}
            @endif
        </p>
    @endif
</div>
