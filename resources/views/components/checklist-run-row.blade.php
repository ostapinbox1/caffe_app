@props(['run', 'showUser' => false])

{{-- Строка чек-листа: название, срок, прогресс и состояние. Нужны загруженные items и shift. --}}
@php($status = $run->status())
<a href="{{ route('checklists.show', $run) }}" wire:navigate
   {{ $attributes->class('block rounded-lg border border-zinc-200 bg-white p-3 transition hover:border-accent dark:border-zinc-700 dark:bg-zinc-800') }}>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <div class="font-medium">{{ $run->title }}</div>
            <div class="text-xs text-zinc-500">
                @if ($showUser){{ $run->user->shortName() }} · @endif
                {{ $run->deadlineLabel() }}
                @if ($run->completed_at) · сдан в {{ $run->completed_at->format('H:i') }}@endif
            </div>
        </div>
        <flux:badge size="sm" :color="$status->color()">{{ $status->label() }}</flux:badge>
    </div>
    <div class="mt-2 flex items-center gap-2">
        <x-progress :value="$run->progressPercent()" :color="match ($status->color()) { 'red' => 'bg-red-500', 'amber' => 'bg-amber-500', default => 'bg-accent' }" />
        <span class="shrink-0 text-xs tabular-nums text-zinc-500">{{ $run->checkedCount() }}/{{ $run->items->count() }}</span>
    </div>
</a>
