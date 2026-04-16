<?php

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function leaderboard(): array
    {
        $event = Event::current();

        if (!$event) {
            return [];
        }

        return $event->leaderboardRows()->all();
    }
};
?>

<div class="border-border bg-card rounded-3xl border p-6 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-muted-foreground text-xs font-semibold uppercase tracking-[0.2em]">Poredak</p>
            <h2 class="font-display mt-2 text-2xl font-semibold">Bodovi nakon svakog meča</h2>
        </div>
        <div class="text-muted-foreground text-xs">Pobjeda = 3 boda, remi = 2 boda, poraz = 1 bod</div>
    </div>

    <div class="border-border/70 mt-6 overflow-hidden rounded-2xl border">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead
                       class="bg-linear-to-r text-muted-foreground from-emerald-400/15 via-amber-400/10 to-sky-400/15 text-xs uppercase tracking-widest">
                    <tr>
                        <th class="px-4 py-3">Igrač</th>
                        <th class="px-4 py-3">
                            <span class="sm:hidden">B</span>
                            <span class="hidden sm:inline">Bodovi</span>
                        </th>
                        <th class="px-4 py-3">
                            <span class="sm:hidden">W</span>
                            <span class="hidden sm:inline">Pobjede</span>
                        </th>
                        <th class="px-4 py-3">
                            <span class="sm:hidden">D</span>
                            <span class="hidden sm:inline">Remiji</span>
                        </th>
                        <th class="px-4 py-3">
                            <span class="sm:hidden">L</span>
                            <span class="hidden sm:inline">Porazi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-border/70 divide-y">
                    @forelse ($this->leaderboard as $row)
                        <tr class="bg-card transition hover:bg-emerald-400/5"
                            wire:key="leaderboard-{{ $row['id'] }}">
                            <td class="text-foreground px-4 py-3 font-semibold">
                                <a href="{{ $row['profile_url'] }}"
                                   class="rounded-md transition hover:text-emerald-600 hover:underline dark:hover:text-emerald-400">
                                    <span class="sm:hidden">{{ $row['short_name'] }}</span>
                                    <span class="hidden sm:inline">{{ $row['name'] }}</span>
                                </a>
                            </td>
                            <td class="text-foreground px-4 py-3 font-semibold">{{ $row['points'] }}</td>
                            <td class="text-muted-foreground px-4 py-3">{{ $row['wins'] }}</td>
                            <td class="text-muted-foreground px-4 py-3">{{ $row['draws'] }}</td>
                            <td class="text-muted-foreground px-4 py-3">{{ $row['losses'] }}</td>
                        </tr>
                    @empty
                        <tr class="bg-card">
                            <td class="text-muted-foreground px-4 py-6 text-center text-sm" colspan="5">
                                Još nema upisanih partija.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
