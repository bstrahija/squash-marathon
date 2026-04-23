<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GameSchedule;
use App\Models\Round;
use Illuminate\Contracts\View\View;

class ScheduleController extends Controller
{
    public function __invoke(): View
    {
        $event = Event::current();

        if (! $event) {
            return view('schedule', [
                'groups' => collect(),
                'schedulesByGroup' => collect(),
            ]);
        }

        $activeRound = Round::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->with('groups')
            ->orderByDesc('number')
            ->first();

        if (! $activeRound) {
            return view('schedule', [
                'groups' => collect(),
                'schedulesByGroup' => collect(),
            ]);
        }

        $groups = $activeRound->groups
            ->whereIn('number', [1, 2])
            ->sortBy('number')
            ->values();

        $groupIds = $groups
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $schedulesByGroup = GameSchedule::query()
            ->with(['playerOne', 'playerTwo', 'game'])
            ->where('round_id', $activeRound->id)
            ->whereIn('group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->reject(fn (GameSchedule $schedule): bool => (bool) $schedule->game?->isFinished())
            ->groupBy('group_id');

        return view('schedule', [
            'groups' => $groups,
            'schedulesByGroup' => $schedulesByGroup,
        ]);
    }
}
