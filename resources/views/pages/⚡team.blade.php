<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Смены')] class extends Component {
    #[Url]
    public string $date = '';

    public function mount(): void
    {
        $this->date = $this->day()->toDateString();
    }

    public function day(): Carbon
    {
        try {
            return $this->date !== '' ? Carbon::parse($this->date)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    public function isToday(): bool
    {
        return $this->day()->isToday();
    }

    /**
     * Смены, начатые в этот день; сегодня — ещё и не закрытые с прошлых дней.
     */
    #[Computed]
    public function shifts()
    {
        $day = $this->day();

        return WorkShift::query()
            ->with(['user.position', 'checklistRuns.items', 'checklistRuns.shift', 'checklistRuns.user'])
            ->where(fn ($query) => $query
                ->whereBetween('started_at', [$day, $day->copy()->endOfDay()])
                ->when($this->isToday(), fn ($query) => $query->orWhereNull('ended_at')))
            ->orderByRaw('ended_at is not null')
            ->orderBy('started_at')
            ->get();
    }

    /**
     * Сотрудники, которые в этот день смену не открывали (графика пока нет — просто для сведения).
     */
    #[Computed]
    public function absent()
    {
        return User::query()
            ->where('cafe_id', Auth::user()->cafe_id)
            ->where('is_active', true)
            ->where('role', UserRole::Staff)
            ->whereNotIn('id', $this->shifts->pluck('user_id'))
            ->with('position')
            ->orderBy('first_name')
            ->get();
    }

    public function shiftDay(int $days): void
    {
        $this->date = $this->day()->addDays($days)->toDateString();
        unset($this->shifts, $this->absent);
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-5" wire:poll.30s>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Смены</flux:heading>
            <flux:text>Кто работает и как идут чек-листы. Обновляется само.</flux:text>
        </div>
        <div class="flex items-center gap-1">
            <flux:button size="sm" icon="chevron-left" wire:click="shiftDay(-1)" aria-label="Предыдущий день" />
            <flux:input type="date" size="sm" wire:model.live="date" class="w-40" />
            <flux:button size="sm" icon="chevron-right" wire:click="shiftDay(1)" :disabled="$this->isToday()" aria-label="Следующий день" />
        </div>
    </div>

    @forelse ($this->shifts as $shift)
        <x-card class="flex flex-col gap-3" wire:key="shift-{{ $shift->id }}">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <flux:avatar :name="$shift->user->name" :initials="$shift->user->initials()" :src="$shift->user->avatarUrl()" />
                    <div>
                        <div class="font-semibold">{{ $shift->user->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $shift->user->position?->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="text-end">
                    @if ($shift->isOpen())
                        <flux:badge color="green" size="sm">в смене</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">смена закрыта</flux:badge>
                    @endif
                    <div class="mt-1 text-xs tabular-nums text-zinc-500">
                        {{ $shift->started_at->format($shift->started_at->isSameDay($this->day()) ? 'H:i' : 'd.m H:i') }}
                        – {{ $shift->ended_at?->format('H:i') ?? 'сейчас' }} · {{ $shift->durationLabel() }}
                    </div>
                </div>
            </div>
            @forelse ($shift->checklistRuns as $run)
                <x-checklist-run-row :run="$run" wire:key="run-{{ $run->id }}" />
            @empty
                <flux:text class="text-sm">Чек-листов на эту смену не было.</flux:text>
            @endforelse
        </x-card>
    @empty
        <x-card>
            <flux:text>{{ $this->isToday() ? 'Сегодня смену ещё никто не открывал.' : 'В этот день смен не было.' }}</flux:text>
        </x-card>
    @endforelse

    @if ($this->absent->isNotEmpty())
        <section>
            <flux:heading class="mb-2">Не открывали смену {{ $this->isToday() ? 'сегодня' : 'в этот день' }}</flux:heading>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->absent as $user)
                    <flux:badge color="zinc">{{ $user->name }}@if ($user->position) · {{ $user->position->name }}@endif</flux:badge>
                @endforeach
            </div>
        </section>
    @endif
</div>
