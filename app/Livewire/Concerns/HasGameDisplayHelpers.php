<?php

namespace App\Livewire\Concerns;

trait HasGameDisplayHelpers
{
    protected function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '—';
        }

        $minutes          = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    protected function playerClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        if (! $playerId) {
            return 'text-foreground';
        }

        if ($isDraw) {
            return 'text-amber-600/90 dark:text-amber-400/90';
        }

        if ($winnerId && $playerId === $winnerId) {
            return 'text-emerald-600 dark:text-emerald-400';
        }

        return 'text-foreground/70';
    }

    protected function setScoreClass(?int $playerId, ?int $winnerId, bool $isDraw): string
    {
        if (! $playerId || $isDraw || ! $winnerId) {
            return 'text-foreground';
        }

        if ($playerId === $winnerId) {
            return 'text-foreground';
        }

        return 'text-foreground/60';
    }
}
