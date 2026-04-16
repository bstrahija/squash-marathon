<?php

use App\Enums\RoleName;
use App\Models\Event;
use App\Models\Round;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public ?int $confirmingDeletionId = null;

    #[Computed]
    public function canManageRounds(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole(RoleName::Admin->value);
    }

    #[Computed]
    public function currentEvent(): ?Event
    {
        return Event::current();
    }

    #[Computed]
    public function roundsCount(): int
    {
        if (!$this->currentEvent) {
            return 0;
        }

        return Round::query()->where('event_id', $this->currentEvent->id)->count();
    }

    #[Computed]
    public function actionButtonLabel(): string
    {
        return $this->roundsCount > 0 ? 'Završi rundu' : 'Započni rundu';
    }

    /**
     * @return array{mode: string}
     */
    #[Computed]
    public function actionButtonRouteParams(): array
    {
        return [
            'mode' => $this->roundsCount > 0 ? 'finish' : 'start',
        ];
    }

    #[Computed]
    public function rounds(): LengthAwarePaginator
    {
        if (!$this->currentEvent) {
            return Round::query()->whereRaw('1 = 0')->paginate(25);
        }

        return Round::query()
            ->with('event')
            ->withCount(['groups', 'games'])
            ->where('event_id', $this->currentEvent->id)
            ->orderByDesc('number')
            ->orderByDesc('id')
            ->paginate(25);
    }

    public function confirmDelete(int $roundId): void
    {
        if (!$this->canManageRounds) {
            return;
        }

        $this->confirmingDeletionId = $roundId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeletionId = null;
    }

    public function deleteRound(int $roundId): void
    {
        if (!$this->canManageRounds) {
            abort(403);
        }

        if ($this->confirmingDeletionId !== $roundId) {
            return;
        }

        Round::query()->findOrFail($roundId)->delete();
        $this->confirmingDeletionId = null;

        session()->flash('status', 'Runda je uspjesno obrisana.');
    }
};
?>

<div>
    <div class="m-6 flex items-center justify-between gap-4">
        <h1 class="font-display text-foreground text-3xl font-semibold">Runde</h1>

        @if ($this->canManageRounds)
            <a href="{{ route('rounds.create', $this->actionButtonRouteParams) }}"
               class="border-border bg-card text-foreground hover:border-foreground/40 rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide transition hover:-translate-y-0.5">
                {{ $this->actionButtonLabel }}
            </a>
        @endif
    </div>

    <div class="border-border bg-card/80 rounded-3xl border p-6 shadow-sm">

        @if (session('status'))
            <div
                 class="mb-4 rounded-2xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-3xl w-full text-left text-sm">
                <thead class="text-muted-foreground text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-3">Runda</th>
                        <th class="px-3 py-3">Naziv</th>
                        <th class="px-3 py-3">Grupe</th>
                        <th class="px-3 py-3">Mečevi</th>
                        <th class="px-3 py-3">Kreirano</th>
                        @if ($this->canManageRounds)
                            <th class="px-3 py-3 text-right">Akcije</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-border/70 divide-y">
                    @forelse ($this->rounds as $round)
                        <tr wire:key="rounds-list-round-{{ $round->id }}">
                            <td class="text-foreground px-3 py-3 font-semibold">#{{ $round->number }}</td>
                            <td class="text-foreground px-3 py-3 font-medium">{{ $round->name }}</td>
                            <td class="text-muted-foreground px-3 py-3">{{ $round->groups_count }}</td>
                            <td class="text-muted-foreground px-3 py-3">{{ $round->games_count }}</td>
                            <td class="text-muted-foreground px-3 py-3">
                                {{ $round->created_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            @if ($this->canManageRounds)
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('rounds.edit', $round->id) }}"
                                           class="border-border text-foreground hover:border-foreground/40 rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition">
                                            Uredi
                                        </a>

                                        @if ($confirmingDeletionId === $round->id)
                                            <button type="button" wire:click="deleteRound({{ $round->id }})"
                                                    class="rounded-full border border-red-500/40 bg-red-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-500/20 dark:text-red-300">
                                                Potvrdi brisanje
                                            </button>
                                            <button type="button" wire:click="cancelDelete"
                                                    class="border-border text-muted-foreground hover:border-foreground/40 hover:text-foreground rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition">
                                                Odustani
                                            </button>
                                        @else
                                            <button type="button" wire:click="confirmDelete({{ $round->id }})"
                                                    class="rounded-full border border-red-500/40 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-500/10 dark:text-red-300">
                                                Obrisi
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted-foreground px-3 py-8 text-center text-sm"
                                colspan="{{ $this->canManageRounds ? 6 : 5 }}">
                                Nema rundi za prikaz.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $this->rounds->links() }}
        </div>
    </div>
</div>
