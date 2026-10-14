@props(['value' => 0, 'color' => 'bg-accent'])

{{-- Полоска прогресса, 0–100. --}}
<div {{ $attributes->class('h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700') }} role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
    <div class="h-full rounded-full {{ $color }} transition-all" style="width: {{ max(0, min(100, $value)) }}%"></div>
</div>
