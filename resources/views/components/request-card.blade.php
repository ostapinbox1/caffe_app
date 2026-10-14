@props(['request'])

{{-- Карточка заявки в списках: номер, раздел, статус и состав одной строкой. --}}
<a href="{{ route('requests.show', $request) }}" wire:navigate
   {{ $attributes->class('block rounded-lg border border-zinc-200 p-3 transition hover:border-accent dark:border-zinc-700') }}>
    <div class="flex items-start justify-between gap-2">
        <div class="font-semibold">
            №{{ $request->number }} · {{ $request->section->name }}
            @if ($request->is_urgent)
                <span class="text-red-600 dark:text-red-400">· срочно</span>
            @endif
        </div>
        <flux:badge size="sm" :color="$request->status->badgeColor()">{{ $request->status->getLabel() }}</flux:badge>
    </div>
    <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $request->summary() }}</div>
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
