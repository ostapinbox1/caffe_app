<?php

use App\Models\ChecklistRun;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Чек-лист')] class extends Component {
    public ChecklistRun $run;

    public string $comment = '';

    public function mount(ChecklistRun $run): void
    {
        // Свой чек-лист или управляющий; коллегам — нет.
        abort_unless($run->user_id === Auth::id() || Auth::user()->isManager(), 404);

        $this->run = $run;
        $this->reload();
    }

    public function toggle(int $itemId): void
    {
        $this->run->toggle($itemId, Auth::user());
        $this->reload();
    }

    public function complete(): void
    {
        $this->run->complete(Auth::user(), $this->comment);
        $this->reload();

        Flux::toast(variant: 'success', text: 'Чек-лист сдан. Спасибо!');
    }

    private function reload(): void
    {
        $this->run->refresh()->load(['items', 'shift', 'user', 'template']);
        $this->comment = (string) ($this->run->comment ?? $this->comment);
    }
}; ?>

@php($status = $run->status())
@php($canFill = $run->canBeFilledBy(auth()->user()))
@php($unchecked = $run->items->count() - $run->checkedCount())
<div class="mx-auto flex w-full max-w-2xl flex-col gap-4" @if (! $canFill) wire:poll.30s @endif>
    <div>
        <flux:link :href="$run->user_id === auth()->id() ? route('checklists.index') : route('team')" wire:navigate class="text-sm">← Назад</flux:link>
    </div>

    <div class="flex items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ $run->title }}</flux:heading>
            <flux:text>
                @if ($run->user_id !== auth()->id()){{ $run->user->name }} · @endif
                {{ $run->shift->started_at->translatedFormat('j F') }} · {{ $run->deadlineLabel() }}
            </flux:text>
        </div>
        <flux:badge :color="$status->color()" size="lg">{{ $status->label() }}</flux:badge>
    </div>

    @if ($run->template?->description)
        <flux:callout icon="information-circle" color="sky">
            <flux:callout.text>{{ $run->template->description }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Прогресс; на телефоне прилипает к шапке --}}
    <div class="sticky top-14 z-10 -mx-4 bg-zinc-50/95 px-4 py-2 backdrop-blur lg:top-0 dark:bg-zinc-900/95">
        <div class="mb-1 flex justify-between text-sm">
            <span>Отмечено {{ $run->checkedCount() }} из {{ $run->items->count() }}</span>
            <span class="font-semibold tabular-nums">{{ $run->progressPercent() }}%</span>
        </div>
        <x-progress :value="$run->progressPercent()" />
    </div>

    @foreach ($run->items->groupBy('section') as $section => $items)
        <section class="flex flex-col gap-1" wire:key="section-{{ $loop->index }}">
            <flux:heading class="px-1 pt-2 uppercase tracking-wide text-zinc-500">{{ $section }}</flux:heading>
            <x-card class="p-0">
                @foreach ($items as $item)
                    @php($checked = $item->checked_at !== null)
                    <button type="button" wire:key="item-{{ $item->id }}"
                            @if ($canFill) wire:click="toggle({{ $item->id }})" @else disabled @endif
                            class="flex w-full items-start gap-3 border-b border-zinc-100 px-4 py-3 text-start last:border-0 disabled:cursor-default dark:border-zinc-700 {{ $checked ? 'bg-emerald-50/60 dark:bg-emerald-950/30' : '' }}">
                        <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full border-2 transition {{ $checked ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-300 dark:border-zinc-500' }}">
                            @if ($checked)
                                <flux:icon.check variant="micro" class="size-4" />
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block {{ $checked ? 'text-zinc-500 line-through decoration-zinc-400' : '' }}">{{ $item->text }}</span>
                            @if ($item->hint)
                                <span class="mt-0.5 block text-xs text-amber-700 dark:text-amber-400">{{ $item->hint }}</span>
                            @endif
                        </span>
                        @if ($checked)
                            <span class="shrink-0 text-xs tabular-nums text-zinc-400">{{ $item->checked_at->format('H:i') }}</span>
                        @endif
                    </button>
                @endforeach
            </x-card>
        </section>
    @endforeach

    @if ($canFill)
        <x-card class="flex flex-col gap-3">
            @if ($unchecked > 0)
                <flux:textarea wire:model="comment" label="Комментарий" rows="2"
                               :placeholder="'Не отмечено пунктов: '.$unchecked.'. Если что-то не получилось — напишите почему.'" />
            @endif
            <flux:button variant="primary" wire:click="complete" class="h-12 w-full" icon="check-circle"
                         :wire:confirm="$unchecked > 0 ? 'Не отмечено пунктов: '.$unchecked.'. Управляющий увидит комментарий. Сдать как есть?' : null">
                Сдать чек-лист
            </flux:button>
        </x-card>
    @elseif ($run->completed_at)
        <flux:callout :color="$status->color() === 'green' ? 'emerald' : 'amber'" icon="check-circle">
            <flux:callout.heading>Сдан {{ $run->completed_at->translatedFormat('j F в H:i') }}</flux:callout.heading>
            @if ($run->comment)
                <flux:callout.text>Комментарий: {{ $run->comment }}</flux:callout.text>
            @endif
        </flux:callout>
    @elseif ($run->shift->ended_at)
        <flux:callout color="red" icon="exclamation-triangle">
            <flux:callout.heading>Смена закрыта, чек-лист не сдан</flux:callout.heading>
        </flux:callout>
    @endif
</div>
