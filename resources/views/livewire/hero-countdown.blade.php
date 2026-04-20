<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function status(): array
    {
        $event = Event::current();

        if (! $event) {
            return ['show' => false];
        }

        $now      = now();
        $startsAt = $event->start_at;
        $endsAt   = $event->end_at;

        $isStarted = $startsAt ? $now->greaterThanOrEqualTo($startsAt) : true;
        $isOver    = $endsAt ? $now->greaterThanOrEqualTo($endsAt) : false;

        if ($isOver) {
            return ['show' => false];
        }

        $target           = $isStarted ? $endsAt : $startsAt;
        $secondsRemaining = $target ? max(0, (int) round($now->diffInSeconds($target, false))) : null;

        return [
            'show'              => true,
            'label'             => $isStarted ? 'Završava za' : 'Počinje za',
            'target_unix'       => $target?->getTimestamp(),
            'remaining_seconds' => $secondsRemaining,
            'remaining_label'   => $secondsRemaining !== null ? $this->formatDuration($secondsRemaining) : '—',
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $days    = intdiv($seconds, 86400);
        $hours   = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs    = $seconds % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days}d";
        }

        if ($days > 0 || $hours > 0) {
            $parts[] = "{$hours}h";
        }

        if ($days > 0 || $hours > 0 || $minutes > 0) {
            $parts[] = "{$minutes}m";
        }

        $parts[] = "{$secs}s";

        return implode(' ', $parts);
    }
};
?>

<div wire:poll.60s
    data-target-unix="{{ $this->status['target_unix'] ?? '' }}"
    x-data="{
        targetEpoch: null,
        displayLabel: '{{ $this->status['remaining_label'] ?? '—' }}',
        formatDuration(seconds) {
            const total = Math.max(0, Number(seconds) || 0);
            const days = Math.floor(total / 86400);
            const hours = Math.floor((total % 86400) / 3600);
            const minutes = Math.floor((total % 3600) / 60);
            const secs = total % 60;

            const parts = [];

            if (days > 0) parts.push(`${days}d`);
            if (days > 0 || hours > 0) parts.push(`${hours}h`);
            if (days > 0 || hours > 0 || minutes > 0) parts.push(`${minutes}m`);
            parts.push(`${secs}s`);

            return parts.join(' ');
        },
        syncFromServer() {
            const raw = this.$el.dataset.targetUnix;
            const next = raw === '' ? null : Number(raw);
            this.targetEpoch = Number.isNaN(next) ? null : next;

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
            const remaining = Math.max(0, this.targetEpoch - nowEpoch);

            this.displayLabel = remaining === 0 ? '00:00:00' : this.formatDuration(remaining);
        },
        init() {
            this.syncFromServer();

            if (this.$el._heroCountdownTimer) {
                window.clearInterval(this.$el._heroCountdownTimer);
            }

            if (this.$el._heroCountdownObserver) {
                this.$el._heroCountdownObserver.disconnect();
            }

            this.$el._heroCountdownTimer = window.setInterval(() => this.tick(), 1000);
            this.$el._heroCountdownObserver = new MutationObserver(() => this.syncFromServer());
            this.$el._heroCountdownObserver.observe(this.$el, {
                attributes: true,
                attributeFilter: ['data-target-unix'],
            });
        }
    }" x-init="init()">
    @if ($this->status['show'])
        <div class="inline-flex items-center gap-3 rounded-2xl border border-border/60 bg-card/70 px-4 py-3 shadow-sm">
            <x-heroicon-o-clock class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <div class="flex items-baseline gap-2">
                <span class="text-sm font-semibold text-muted-foreground hero-countdown-label">
                    {{ $this->status['label'] }}
                </span>
                <span class="font-display tabular-nums text-xl font-semibold text-foreground hero-countdown-timer"
                    x-text="displayLabel">
                    {{ $this->status['remaining_label'] }}
                </span>
            </div>
        </div>
    @endif
</div>
