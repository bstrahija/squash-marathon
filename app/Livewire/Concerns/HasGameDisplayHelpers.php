<?php

namespace App\Livewire\Concerns;

use App\Models\Game;
use Illuminate\Support\Carbon;

trait HasGameDisplayHelpers
{
    /**
     * Return the best duration label for a game.
     *
     * For live games a real-time elapsed value is computed from `started_at`.
     * For finished games the stored `duration_seconds` is preferred; if that is
     * absent the value is derived from `started_at` / `finished_at`.
     */
    protected function matchDurationLabel(Game $game, bool $isLive): string
    {
        if ($isLive && $game->started_at) {
            $seconds = max(0, (int) $game->started_at->diffInSeconds(Carbon::now()));

            return $this->formatDuration($seconds);
        }

        if ($game->duration_seconds) {
            return $this->formatDuration($game->duration_seconds);
        }

        if ($game->started_at && $game->finished_at) {
            $seconds = max(0, (int) $game->started_at->diffInSeconds($game->finished_at));

            return $this->formatDuration($seconds);
        }

        if ($game->started_at && ! $game->finished_at) {
            $seconds = max(0, (int) $game->started_at->diffInSeconds(Carbon::now()));

            return $this->formatDuration($seconds);
        }

        return '—';
    }

    protected function formatDuration(?int $seconds): string
    {
        if (! $seconds) {
            return '—';
        }

        $hours            = intdiv($seconds, 3600);
        $minutes          = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }

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
