<?php

use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use App\Models\RequestSection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Заявки')] class extends Component {
    #[Url]
    public string $section = '';

    #[Url]
    public bool $mine = false;

    /**
     * Колонки доски: статус → заголовок. Получено и отменено — только за неделю.
     *
     * @return array<string, string>
     */
    public function columns(): array
    {
        return [
            RequestStatus::New->value => 'Новые',
            RequestStatus::Accepted->value => 'Приняты',
            RequestStatus::Ordered->value => 'Заказано',
            RequestStatus::Received->value => 'Получено',
        ];
    }

    #[Computed]
    public function sections()
    {
        return RequestSection::query()->orderBy('sort')->get();
    }

    #[Computed]
    public function board()
    {
        return PurchaseRequest::query()
            ->with(['section', 'author', 'items'])
            ->when($this->section !== '', fn ($query) => $query->where('request_section_id', (int) $this->section))
            ->when($this->mine, fn ($query) => $query->where('author_id', Auth::id()))
            ->where(fn ($query) => $query
                ->open()
                ->orWhere(fn ($query) => $query
                    ->where('status', RequestStatus::Received)
                    ->where('status_changed_at', '>=', now()->subDays(7))))
            ->orderByDesc('is_urgent')
            ->latest()
            ->get()
            ->groupBy(fn (PurchaseRequest $request) => $request->status->value);
    }

    #[Computed]
    public function cancelled()
    {
        return PurchaseRequest::query()
            ->with(['section', 'author', 'items'])
            ->where('status', RequestStatus::Cancelled)
            ->where('status_changed_at', '>=', now()->subDays(7))
            ->when($this->mine, fn ($query) => $query->where('author_id', Auth::id()))
            ->latest()
            ->get();
    }
}; ?>

<div class="flex w-full flex-col gap-4" wire:poll.30s>
    <div class="flex items-center justify-between gap-2">
        <div>
            <flux:heading size="xl">Заявки</flux:heading>
            <flux:text>Все заявки кофейни по этапам. Получено — за последнюю неделю.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('requests.create')" wire:navigate>
            <span class="max-sm:hidden">Новая заявка</span><span class="sm:hidden">Новая</span>
        </flux:button>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <flux:button size="sm" :variant="$section === '' ? 'primary' : 'filled'" wire:click="$set('section', '')">Все разделы</flux:button>
        @foreach ($this->sections as $item)
            <flux:button size="sm" :variant="$section === (string) $item->id ? 'primary' : 'filled'" wire:click="$set('section', '{{ $item->id }}')">
                {{ $item->name }}
            </flux:button>
        @endforeach
        <flux:switch wire:model.live="mine" label="Только мои" class="ms-auto" align="left" />
    </div>

    {{-- Телефон: этапы с числами — нажмите, чтобы перелистнуть к колонке --}}
    <div class="grid grid-cols-4 gap-1 md:hidden">
        @foreach ($this->columns() as $status => $title)
            @php($statusEnum = \App\Enums\RequestStatus::from($status))
            <a href="#col-{{ $status }}" class="flex flex-col items-center rounded-lg bg-white py-1.5 text-xs shadow-xs dark:bg-zinc-800">
                <span class="text-base font-semibold tabular-nums">{{ $this->board->get($status, collect())->count() }}</span>
                <span class="text-zinc-500">{{ $title }}</span>
            </a>
        @endforeach
    </div>

    {{-- Доска: на телефоне колонки листаются вбок --}}
    <div class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-4 md:overflow-visible md:px-0">
        @foreach ($this->columns() as $status => $title)
            @php($items = $this->board->get($status, collect()))
            @php($statusEnum = \App\Enums\RequestStatus::from($status))
            <section id="col-{{ $status }}" class="flex w-[85%] shrink-0 snap-start flex-col gap-2 rounded-xl bg-zinc-100 p-2 md:w-auto dark:bg-zinc-800/60" wire:key="col-{{ $status }}">
                <div class="flex items-center justify-between px-1 py-1">
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <flux:badge size="sm" :color="$statusEnum->badgeColor()">{{ $title }}</flux:badge>
                    </span>
                    <span class="text-sm tabular-nums text-zinc-500">{{ $items->count() }}</span>
                </div>
                @forelse ($items as $request)
                    <x-request-card :request="$request" :show-status="false" wire:key="request-{{ $request->id }}" />
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-400 dark:border-zinc-700">Пусто</div>
                @endforelse
            </section>
        @endforeach
    </div>

    @if ($this->cancelled->isNotEmpty())
        <details class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <summary class="text-sm text-zinc-500">Отменённые за неделю ({{ $this->cancelled->count() }})</summary>
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach ($this->cancelled as $request)
                    <x-request-card :request="$request" wire:key="cancelled-{{ $request->id }}" />
                @endforeach
            </div>
        </details>
    @endif
</div>
