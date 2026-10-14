@props(['request'])

{{-- Карточка заявки: номер, раздел, статус (цветная полоса слева) и состав одной строкой. --}}
@php($stripe = match ($request->status) {
    \App\Enums\RequestStatus::New => 'border-l-sky-500',
    \App\Enums\RequestStatus::Accepted => 'border-l-amber-500',
    \App\Enums\RequestStatus::Ordered => 'border-l-emerald-500',
    \App\Enums\RequestStatus::Received => 'border-l-green-600',
    \App\Enums\RequestStatus::Cancelled => 'border-l-zinc-400',
})
<a href="{{ route('requests.show', $request) }}" wire:navigate
   {{ $attributes->class(['block rounded-lg border border-l-4 border-zinc-200 bg-white p-3 shadow-xs transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800', $stripe]) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="font-semibold">
            №{{ $request->number }} · {{ $request->section->name }}
            @if ($request->is_urgent)
                <flux:badge size="sm" color="red" class="ms-1">срочно</flux:badge>
            @endif
        </div>
        <flux:badge size="sm" :color="$request->status->badgeColor()">{{ $request->status->getLabel() }}</flux:badge>
    </div>
    <div class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $request->summary() }}</div>
    <div class="mt-1 text-xs text-zinc-500">
        {{ $request->author->shortName() }} · {{ $request->created_at->translatedFormat('j M, H:i') }}
        @if ($request->needed_by)
            · нужно к {{ $request->needed_by->translatedFormat('j M') }}
        @endif
        @if ($request->expected_at && $request->status->isOpen())
            · поставка {{ $request->expected_at->translatedFormat('j M') }}
        @endif
    </div>
</a>
