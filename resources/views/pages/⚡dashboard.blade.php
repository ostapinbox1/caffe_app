<?php

use App\Actions\Shifts\EndShift;
use App\Actions\Shifts\StartShift;
use App\Enums\RequestStatus;
use App\Models\ChecklistRun;
use App\Models\ChecklistTemplate;
use App\Models\PurchaseRequest;
use App\Models\WorkShift;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Главная')] class extends Component {
    #[Computed]
    public function shift(): ?WorkShift
    {
        return Auth::user()->openShift()->with(['checklistRuns.items', 'checklistRuns.shift'])->first();
    }

    /**
     * Что выдадут на смене — показываем до её начала.
     */
    #[Computed]
    public function upcomingChecklists()
    {
        return ChecklistTemplate::query()->assignedTo(Auth::user())->orderBy('sort')->get();
    }

    #[Computed]
    public function myOpenRequests()
    {
        return PurchaseRequest::query()
            ->open()
            ->where('author_id', Auth::id())
            ->with(['section', 'items', 'author'])
            ->latest()
            ->limit(3)
            ->get();
    }

    /**
     * Сводка дня для управляющего.
     *
     * @return array{onShift: int, overdue: int, newRequests: int, urgent: int}
     */
    #[Computed]
    public function managerSummary(): array
    {
        return [
            'onShift' => WorkShift::query()->open()->count(),
            'overdue' => ChecklistRun::query()->overdue()->count(),
            'newRequests' => PurchaseRequest::query()->where('status', RequestStatus::New)->count(),
            'urgent' => PurchaseRequest::query()->open()->where('is_urgent', true)->count(),
        ];
    }

    public function startShift(StartShift $start): void
    {
        $shift = $start->handle(Auth::user());
        unset($this->shift);

        $count = $shift->checklistRuns()->count();
        Flux::toast(variant: 'success', text: $count > 0 ? "Смена начата. Чек-листов на смену: {$count}." : 'Смена начата.');
    }

    public function endShift(EndShift $end): void
    {
        $end->handle(Auth::user());
        unset($this->shift);

        Flux::toast(variant: 'success', text: 'Смена закончена. Хорошего отдыха!');
    }

    public function greeting(): string
    {
        return match (true) {
            now()->hour >= 5 && now()->hour < 12 => 'Доброе утро',
            now()->hour >= 12 && now()->hour < 18 => 'Добрый день',
            now()->hour >= 18 && now()->hour < 23 => 'Добрый вечер',
            default => 'Доброй ночи',
        };
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5">
    <div>
        <flux:heading size="xl">{{ $this->greeting() }}, {{ auth()->user()->greetingName() }}!</flux:heading>
        <flux:text>{{ auth()->user()->position?->name ?? auth()->user()->role->getLabel() }} · {{ auth()->user()->cafe?->name }}</flux:text>
    </div>

    {{-- Смена --}}
    @if ($this->shift)
        @php($openRuns = $this->shift->checklistRuns->whereNull('completed_at')->count())
        <x-card class="border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="relative flex size-3">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-green-500 opacity-60"></span>
                        <span class="relative inline-flex size-3 rounded-full bg-green-600"></span>
                    </span>
                    <div>
                        <div class="font-semibold">Смена идёт</div>
                        <div class="text-sm text-zinc-500">с {{ $this->shift->started_at->format('H:i') }} · {{ $this->shift->durationLabel() }}</div>
                    </div>
                </div>
                <flux:button wire:click="endShift" icon="stop-circle"
                             wire:confirm="{{ $openRuns > 0 ? 'Не сданы чек-листы: '.$openRuns.'. Управляющий получит уведомление. Всё равно закончить смену?' : 'Закончить смену?' }}">
                    Закончить смену
                </flux:button>
            </div>
        </x-card>

        <section class="flex flex-col gap-2">
            <flux:heading size="lg">Чек-листы на смену</flux:heading>
            @forelse ($this->shift->checklistRuns as $run)
                <x-checklist-run-row :run="$run" wire:key="run-{{ $run->id }}" />
            @empty
                <flux:text>На эту смену чек-листов нет.</flux:text>
            @endforelse
        </section>
    @else
        <x-card>
            <div class="flex flex-col gap-3">
                <div>
                    <div class="font-semibold">Смена не начата</div>
                    <div class="text-sm text-zinc-500">
                        @if ($this->upcomingChecklists->isNotEmpty())
                            Начните смену — появятся чек-листы: {{ $this->upcomingChecklists->pluck('name')->implode(', ') }}.
                        @else
                            Отметьте начало работы — управляющий увидит, кто в смене.
                        @endif
                    </div>
                </div>
                <flux:button variant="primary" icon="play-circle" wire:click="startShift" class="h-12 w-full sm:w-auto">
                    Начать смену
                </flux:button>
            </div>
        </x-card>
    @endif

    {{-- Управляющему: что требует внимания --}}
    @if (auth()->user()->isManager())
        @php($summary = $this->managerSummary)
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a href="{{ route('team') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="text-2xl font-semibold">{{ $summary['onShift'] }}</div>
                <div class="text-xs text-zinc-500">в смене сейчас</div>
            </a>
            <a href="{{ route('team') }}" wire:navigate class="rounded-xl border p-3 {{ $summary['overdue'] ? 'border-red-300 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' }}">
                <div class="text-2xl font-semibold">{{ $summary['overdue'] }}</div>
                <div class="text-xs opacity-75">просрочено чек-листов</div>
            </a>
            <a href="{{ route('requests.index') }}" wire:navigate class="rounded-xl border p-3 {{ $summary['newRequests'] ? 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' }}">
                <div class="text-2xl font-semibold">{{ $summary['newRequests'] }}</div>
                <div class="text-xs opacity-75">новых заявок</div>
            </a>
            <a href="{{ route('requests.index') }}" wire:navigate class="rounded-xl border p-3 {{ $summary['urgent'] ? 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800' }}">
                <div class="text-2xl font-semibold">{{ $summary['urgent'] }}</div>
                <div class="text-xs opacity-75">срочных в работе</div>
            </a>
        </section>
    @endif

    {{-- Мои заявки --}}
    <section class="flex flex-col gap-2">
        <div class="flex items-center justify-between gap-2">
            <flux:heading size="lg">Мои заявки</flux:heading>
            <flux:button size="sm" variant="primary" icon="plus" :href="route('requests.create')" wire:navigate>Новая заявка</flux:button>
        </div>
        @forelse ($this->myOpenRequests as $request)
            <x-request-card :request="$request" wire:key="request-{{ $request->id }}" />
        @empty
            <flux:text>Открытых заявок нет.</flux:text>
        @endforelse
    </section>
</div>
