<?php

use Livewire\Component;

new class extends Component {
    public string $activeTab = 'main';

    public function setTab(string $tab): void
    {
        if (!in_array($tab, ['main', 'group-1', 'group-2'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }
};
?>

<div>
    <div class="flex flex-wrap gap-2 rounded-3xl border border-border bg-card/70 p-2 shadow-sm">
        <button type="button" wire:click="setTab('main')" @class([
            'rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition',
            'bg-foreground text-background shadow-sm' => $activeTab === 'main',
            'border border-border bg-card text-foreground hover:border-foreground/40 hover:-translate-y-0.5' =>
                $activeTab !== 'main',
        ])>
            Glavni poredak
        </button>

        <button type="button" wire:click="setTab('group-1')" @class([
            'rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition',
            'bg-foreground text-background shadow-sm' => $activeTab === 'group-1',
            'border border-border bg-card text-foreground hover:border-foreground/40 hover:-translate-y-0.5' =>
                $activeTab !== 'group-1',
        ])>
            Grupa 1
        </button>

        <button type="button" wire:click="setTab('group-2')" @class([
            'rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition',
            'bg-foreground text-background shadow-sm' => $activeTab === 'group-2',
            'border border-border bg-card text-foreground hover:border-foreground/40 hover:-translate-y-0.5' =>
                $activeTab !== 'group-2',
        ])>
            Grupa 2
        </button>
    </div>

    <div class="mt-6">
        @if ($activeTab === 'main')
            <livewire:stats.leaderboard :key="'stats-main-leaderboard'" />
        @elseif ($activeTab === 'group-1')
            <livewire:stats.leaderboard :group-number="1" :key="'stats-group-one-leaderboard'" />
        @else
            <livewire:stats.leaderboard :group-number="2" :key="'stats-group-two-leaderboard'" />
        @endif
    </div>
</div>
