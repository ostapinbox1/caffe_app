<?php

use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Заявка')] class extends Component {
    public PurchaseRequest $purchaseRequest;

    public string $managerComment = '';

    public string $expectedAt = '';

    public string $status = '';

    public function mount(PurchaseRequest $purchaseRequest): void
    {
        $this->purchaseRequest = $purchaseRequest;
        $this->fillManagerForm();
    }

    public function cancel(): void
    {
        $this->purchaseRequest->cancelBy(Auth::user());
        $this->fillManagerForm();

        Flux::toast(variant: 'success', text: 'Заявка отменена.');
    }

    /**
     * Кнопка «следующий шаг»: Принять → Заказано → Получено.
     */
    public function advance(): void
    {
        $this->authorizeManager();

        $next = $this->nextStatus();
        abort_if($next === null, 422);

        $this->purchaseRequest->updateByManager(Auth::user(), ['status' => $next]);
        $this->fillManagerForm();

        Flux::toast(variant: 'success', text: "Заявка №{$this->purchaseRequest->number}: {$next->getLabel()}");
    }

    public function saveAnswer(): void
    {
        $this->authorizeManager();

        $this->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(RequestStatus::class)],
            'managerComment' => ['nullable', 'string', 'max:1000'],
            'expectedAt' => ['nullable', 'date'],
        ]);

        $this->purchaseRequest->updateByManager(Auth::user(), [
            'status' => $this->status,
            'manager_comment' => $this->managerComment,
            'expected_at' => $this->expectedAt ?: null,
        ]);
        $this->fillManagerForm();

        Flux::toast(variant: 'success', text: 'Сохранено. Автор получит уведомление.');
    }

    public function nextStatus(): ?RequestStatus
    {
        return match ($this->purchaseRequest->status) {
            RequestStatus::New => RequestStatus::Accepted,
            RequestStatus::Accepted => RequestStatus::Ordered,
            RequestStatus::Ordered => RequestStatus::Received,
            default => null,
        };
    }

    private function fillManagerForm(): void
    {
        $this->purchaseRequest->refresh()->load(['section', 'author', 'items', 'events.user']);
        $this->status = $this->purchaseRequest->status->value;
        $this->managerComment = (string) $this->purchaseRequest->manager_comment;
        $this->expectedAt = (string) $this->purchaseRequest->expected_at?->toDateString();
    }

    private function authorizeManager(): void
    {
        abort_unless(Auth::user()->isManager(), 403);
    }
}; ?>

@php($request = $purchaseRequest)
<div class="mx-auto flex w-full max-w-2xl flex-col gap-5">
    @if (session('status'))
        <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
    @endif

    <div>
        <flux:link :href="route('requests.index')" wire:navigate class="text-sm">← Все заявки</flux:link>
    </div>

    <div class="flex items-start justify-between gap-3">
        <div>
            <flux:heading size="xl">Заявка №{{ $request->number }} · {{ $request->section->name }}</flux:heading>
            <flux:text>{{ $request->author->name }} · {{ $request->created_at->translatedFormat('j F, H:i') }}</flux:text>
        </div>
        <flux:badge :color="$request->status->badgeColor()" size="lg">{{ $request->status->getLabel() }}</flux:badge>
    </div>

    @if ($request->is_urgent || $request->needed_by)
        <div class="flex flex-wrap gap-2">
            @if ($request->is_urgent)
                <flux:badge color="red" icon="exclamation-triangle">Срочно</flux:badge>
            @endif
            @if ($request->needed_by)
                <flux:badge color="zinc" icon="calendar">Нужно к {{ $request->needed_by->translatedFormat('j F') }}</flux:badge>
            @endif
        </div>
    @endif

    <x-card class="p-0">
        @foreach ($request->items as $item)
            <div class="flex justify-between gap-3 border-b border-zinc-100 px-4 py-2.5 last:border-0 dark:border-zinc-700">
                <span>{{ $item->name }}</span>
                <span class="shrink-0 font-medium tabular-nums">{{ $item->formattedQuantity() }} {{ $item->unit->getLabel() }}</span>
            </div>
        @endforeach
    </x-card>

    @if ($request->comment)
        <div>
            <flux:heading>Комментарий автора</flux:heading>
            <flux:text class="whitespace-pre-line">{{ $request->comment }}</flux:text>
        </div>
    @endif

    @if ($request->manager_comment || $request->expected_at)
        <flux:callout icon="chat-bubble-left-right" color="emerald">
            <flux:callout.heading>Ответ управляющего</flux:callout.heading>
            <flux:callout.text>
                @if ($request->expected_at)
                    Ожидаемая поставка: {{ $request->expected_at->translatedFormat('j F') }}.<br>
                @endif
                {{ $request->manager_comment }}
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- Обработка — только управляющему --}}
    @if (auth()->user()->isManager())
        <x-card class="flex flex-col gap-4 border-accent/40">
            <flux:heading size="lg">Обработка</flux:heading>
            @if ($next = $this->nextStatus())
                <flux:button variant="primary" wire:click="advance" icon="arrow-right-circle" class="h-12 w-full">
                    {{ match ($next) { \App\Enums\RequestStatus::Accepted => 'Принять заявку', \App\Enums\RequestStatus::Ordered => 'Отметить: заказано', default => 'Отметить: получено' } }}
                </flux:button>
            @endif
            <form wire:submit="saveAnswer" class="flex flex-col gap-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <flux:select wire:model="status" label="Статус">
                        @foreach (\App\Enums\RequestStatus::cases() as $case)
                            <flux:select.option value="{{ $case->value }}">{{ $case->getLabel() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input type="date" wire:model="expectedAt" label="Ожидаемая поставка" />
                </div>
                <flux:textarea wire:model="managerComment" label="Ответ для кофейни" rows="2" placeholder="Например: привезут в четверг утром" />
                <div>
                    <flux:button type="submit">Сохранить ответ</flux:button>
                </div>
            </form>
        </x-card>
    @endif

    <div class="flex flex-wrap gap-2">
        <flux:button icon="arrow-path" :href="route('requests.create', ['repeat' => $request->id])" wire:navigate>
            Повторить заявку
        </flux:button>
        @if ($request->canBeCancelledBy(auth()->user()))
            <flux:button variant="danger" icon="x-mark" wire:click="cancel" wire:confirm="Отменить заявку №{{ $request->number }}?">
                Отменить
            </flux:button>
        @endif
    </div>

    <section class="flex flex-col gap-2">
        <flux:heading>История</flux:heading>
        <ol class="flex flex-col gap-3 border-s-2 border-zinc-200 ps-4 dark:border-zinc-700">
            @foreach ($request->events as $event)
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
