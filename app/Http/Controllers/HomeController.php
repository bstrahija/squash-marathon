<?php

namespace App\Http\Controllers;

use App\Actions\GetEventPlayerStatsAction;
use App\Models\Event;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(GetEventPlayerStatsAction $stats): View
    {
        $event = Event::query()->latest('start_at')->first();

        if (! $event) {
            return view('home', [
                'participants' => [],
            ]);
        }

        $participants = $stats->execute($event)
            ->values()
            ->sortBy(fn (array $row): string => $row['player']->first_name)
            ->map(fn (array $row): array => [
                'name'        => $row['player']->full_name,
                'initials'    => $row['player']->initials,
                'avatar_url'  => $row['player']->hasMedia('avatar') ? $row['player']->avatarUrl('thumb') : null,
                'profile_url' => route('players.show', ['user' => $row['player']->id]),
                'wins'        => $row['wins'],
                'draws'       => $row['draws'],
                'losses'      => $row['losses'],
                'games'       => $row['games'],
            ])
            ->values()
            ->all();

        return view('home', [
            'participants' => $participants,
        ]);
    }
}
