<?php

use App\Models\ChecklistRun;
use App\Models\WorkShift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Чек-листы')] class extends Component {
    #[Computed]
    public function shift(): ?WorkShift
    {
        return Auth::user()->openShift()->with(['checklistRuns.items', 'checklistRuns.shift'])->first();
    }

    /**
     * Мои чек-листы за прошлые смены — две недели.
     */
    #[Computed]
    public function history()
    {
        return ChecklistRun::query()
            ->where('user_id', Auth::id())
            ->whereHas('shift', fn ($query) => $query->whereNotNull('ended_at'))
            ->where('created_at', '>=', now()->subDays(14))
            ->with(['items', 'shift'])
            ->latest()
            ->get()
            ->groupBy(fn (ChecklistRun $run) => $run->shift->started_at->translatedFormat('j F, l'));
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5">
    <div class="flex items-start justify-between gap-2">
        <div>
            <flux:heading size="xl">Чек-листы</flux:heading>
            <flux:text>Выдаются, когда вы начинаете смену.</flux:text>
        </div>
        @if (auth()->user()->isManager())
            <flux:button icon="user-group" :href="route('team')" wire:navigate>Смены команды</flux:button>
        @endif
    </div>

    <section class="flex flex-col gap-2">
        <flux:heading size="lg">Текущая смена</flux:heading>
        @if (! $this->shift)
            <x-card>
                <flux:text>Смена не начата. Начните её на главной — и чек-листы появятся здесь.</flux:text>
                <flux:button class="mt-3" variant="primary" :href="route('dashboard')" wire:navigate>На главную</flux:button>
            </x-card>
        @else
            @forelse ($this->shift->checklistRuns as $run)
                <x-checklist-run-row :run="$run" wire:key="run-{{ $run->id }}" />
            @empty
                <flux:text>На эту смену чек-листов нет.</flux:text>
            @endforelse
        @endif
    </section>

    @foreach ($this->history as $day => $runs)
        <section class="flex flex-col gap-2" wire:key="day-{{ $loop->index }}">
            <flux:heading>{{ Str::ucfirst($day) }}</flux:heading>
            @foreach ($runs as $run)
                <x-checklist-run-row :run="$run" wire:key="history-{{ $run->id }}" />
            @endforeach
        </section>
    @endforeach
</div>
