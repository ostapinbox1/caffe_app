<?php

use App\Models\PurchaseRequest;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Заявка')] class extends Component {
    public PurchaseRequest $purchaseRequest;

    public function mount(PurchaseRequest $purchaseRequest): void
    {
        $this->purchaseRequest = $purchaseRequest->load(['section', 'author', 'items', 'events.user']);
    }

    public function cancel(): void
    {
        $this->purchaseRequest->cancelBy(Auth::user());
        $this->purchaseRequest->load('events.user');

        Flux::toast(variant: 'success', text: 'Заявка отменена.');
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5">
    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div class="flex items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">Заявка №{{ $purchaseRequest->number }}</flux:heading>
            <flux:text>
                {{ $purchaseRequest->section->name }} · {{ $purchaseRequest->author->name }} ·
                {{ $purchaseRequest->created_at->translatedFormat('j F, H:i') }}
            </flux:text>
        </div>
        <flux:badge :color="$purchaseRequest->status->badgeColor()">{{ $purchaseRequest->status->getLabel() }}</flux:badge>
    </div>

    @if ($purchaseRequest->is_urgent || $purchaseRequest->needed_by)
        <div class="flex flex-wrap gap-2">
            @if ($purchaseRequest->is_urgent)
                <flux:badge color="red" icon="exclamation-triangle">Срочно</flux:badge>
            @endif
            @if ($purchaseRequest->needed_by)
                <flux:badge color="zinc" icon="calendar">Нужно к {{ $purchaseRequest->needed_by->translatedFormat('j F') }}</flux:badge>
            @endif
        </div>
    @endif

    <section class="rounded-lg border border-zinc-200 dark:border-zinc-700">
        @foreach ($purchaseRequest->items as $item)
            <div class="flex justify-between gap-3 border-b border-zinc-100 px-3 py-2 last:border-0 dark:border-zinc-800">
                <span>{{ $item->name }}</span>
                <span class="shrink-0 font-medium">{{ $item->formattedQuantity() }} {{ $item->unit->getLabel() }}</span>
            </div>
        @endforeach
    </section>

    @if ($purchaseRequest->comment)
        <div>
            <flux:heading>Комментарий</flux:heading>
            <flux:text class="whitespace-pre-line">{{ $purchaseRequest->comment }}</flux:text>
        </div>
    @endif

    @if ($purchaseRequest->manager_comment || $purchaseRequest->expected_at)
        <flux:callout icon="chat-bubble-left-right">
            <flux:callout.heading>Ответ управляющего</flux:callout.heading>
            <flux:callout.text>
                @if ($purchaseRequest->expected_at)
                    Ожидаемая поставка: {{ $purchaseRequest->expected_at->translatedFormat('j F') }}.<br>
                @endif
                {{ $purchaseRequest->manager_comment }}
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="flex flex-wrap gap-2">
        <flux:button icon="arrow-path" :href="route('requests.create', ['repeat' => $purchaseRequest->id])" wire:navigate>
            Повторить заявку
        </flux:button>
        @if ($purchaseRequest->canBeCancelledBy(auth()->user()))
            <flux:button variant="danger" icon="x-mark" wire:click="cancel" wire:confirm="Отменить заявку №{{ $purchaseRequest->number }}?">
                Отменить
            </flux:button>
        @endif
        @if (auth()->user()->isManager())
            <flux:button icon="cog-6-tooth" href="/admin/purchase-requests/{{ $purchaseRequest->id }}">
                Обработать
            </flux:button>
        @endif
    </div>

    <section class="flex flex-col gap-2">
        <flux:heading>История</flux:heading>
        <ol class="flex flex-col gap-2 border-s border-zinc-200 ps-4 dark:border-zinc-700">
            @foreach ($purchaseRequest->events as $event)
                <li>
                    <div class="text-sm">{{ $event->description() }}</div>
                    <div class="text-xs text-zinc-500">
                        {{ $event->created_at->translatedFormat('j M, H:i') }} · {{ $event->user?->name ?? '—' }}
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
</div>
