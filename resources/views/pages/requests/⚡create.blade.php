<?php

use App\Actions\PurchaseRequests\CreatePurchaseRequest;
use App\Enums\Unit;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RequestSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Новая заявка')] class extends Component {
    public ?int $sectionId = null;

    public string $search = '';

    /** @var list<array{catalog_item_id: int|null, name: string, unit: string, quantity: float|string}> */
    public array $lines = [];

    public string $customName = '';

    public string $customUnit = 'pcs';

    public string $neededBy = '';

    public bool $isUrgent = false;

    public string $comment = '';

    /**
     * «Повторить заявку»: ?repeat=<id> — тот же раздел и состав.
     */
    public function mount(): void
    {
        $repeatId = request()->integer('repeat');

        if ($repeatId > 0) {
            $source = PurchaseRequest::query()->with('items')->find($repeatId);

            if ($source !== null) {
                $this->sectionId = $source->request_section_id;
                $this->lines = $source->items->map(fn (PurchaseRequestItem $item) => [
                    'catalog_item_id' => $item->catalog_item_id,
                    'name' => $item->name,
                    'unit' => $item->unit->value,
                    'quantity' => (float) $item->quantity,
                ])->all();
            }
        }
    }

    protected function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'lines.*.unit' => ['required', Rule::enum(Unit::class)],
            'neededBy' => ['nullable', 'date', 'after_or_equal:today'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'lines.required' => 'Добавьте хотя бы одну позицию.',
            'lines.min' => 'Добавьте хотя бы одну позицию.',
            'lines.*.quantity.*' => 'Проверьте количество.',
            'neededBy.after_or_equal' => 'Дата не может быть в прошлом.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['neededBy' => '«Нужно к»', 'comment' => 'комментарий'];
    }

    #[Computed]
    public function sections()
    {
        return RequestSection::query()->orderBy('sort')->get();
    }

    #[Computed]
    public function section(): ?RequestSection
    {
        return $this->sectionId ? RequestSection::query()->find($this->sectionId) : null;
    }

    /**
     * Категории раздела с видимыми позициями, отфильтрованные поиском.
     */
    #[Computed]
    public function categories()
    {
        $search = mb_strtolower(trim($this->search));

        return CatalogCategory::query()
            ->where('request_section_id', $this->sectionId)
            ->with(['items' => fn ($query) => $query->visible()])
            ->orderBy('sort')
            ->get()
            ->each(function (CatalogCategory $category) use ($search): void {
                if ($search !== '') {
                    $category->setRelation('items', $category->items
                        ->filter(fn (CatalogItem $item) => str_contains(mb_strtolower($item->name), $search))
                        ->values());
                }
            })
            ->filter(fn (CatalogCategory $category) => $category->items->isNotEmpty());
    }

    /**
     * Что из справочника уже есть в открытых заявках раздела: защита от дублей.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function alreadyOrdered(): array
    {
        return PurchaseRequestItem::query()
            ->whereNotNull('catalog_item_id')
            ->whereHas('purchaseRequest', fn ($query) => $query->open()->where('request_section_id', $this->sectionId))
            ->with('purchaseRequest')
            ->get()
            ->groupBy('catalog_item_id')
            ->map(fn ($items) => $items
                ->map(fn (PurchaseRequestItem $item) => "№{$item->purchaseRequest->number}: {$item->formattedQuantity()} {$item->unit->getLabel()}, {$item->purchaseRequest->status->getLabel()}")
                ->implode('; '))
            ->all();
    }

    public function chooseSection(int $id): void
    {
        $this->sectionId = RequestSection::query()->findOrFail($id)->id;
        $this->lines = [];
        $this->search = '';
    }

    public function changeSection(): void
    {
        $this->sectionId = null;
        $this->lines = [];
        $this->search = '';
    }

    public function addItem(int $id): void
    {
        $item = CatalogItem::query()->visible()->findOrFail($id);

        foreach ($this->lines as $index => $line) {
            if ($line['catalog_item_id'] === $item->id) {
                $this->increment($index);

                return;
            }
        }

        $this->lines[] = [
            'catalog_item_id' => $item->id,
            'name' => $item->name,
            'unit' => $item->unit->value,
            'quantity' => max($item->unit->step(), 1),
        ];
    }

    public function addCustom(): void
    {
        $this->validate([
            'customName' => ['required', 'string', 'max:150'],
            'customUnit' => ['required', Rule::enum(Unit::class)],
        ], ['customName.required' => 'Напишите, что нужно.']);

        $unit = Unit::from($this->customUnit);

        $this->lines[] = [
            'catalog_item_id' => null,
            'name' => trim($this->customName),
            'unit' => $unit->value,
            'quantity' => max($unit->step(), 1),
        ];

        $this->customName = '';
    }

    public function increment(int $index): void
    {
        $this->step($index, 1);
    }

    public function decrement(int $index): void
    {
        $this->step($index, -1);
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Запятая вместо точки в количестве — нормально для телефона.
     */
    public function updatedLines(mixed $value, string $key): void
    {
        if (str_ends_with($key, '.quantity')) {
            $index = (int) $key;
            $this->lines[$index]['quantity'] = str_replace([',', ' '], ['.', ''], (string) $value);
        }
    }

    public function submit(CreatePurchaseRequest $create): void
    {
        $this->validate();

        abort_if($this->section === null, 422);

        $request = $create->handle(Auth::user(), $this->section, $this->lines, [
            'needed_by' => $this->neededBy ?: null,
            'is_urgent' => $this->isUrgent,
            'comment' => $this->comment,
        ]);

        session()->flash('status', "Заявка №{$request->number} отправлена управляющему.");

        $this->redirectRoute('requests.show', ['purchaseRequest' => $request], navigate: true);
    }

    /**
     * Где позиция справочника лежит в заявке: [id позиции => номер строки].
     *
     * @return array<int, int>
     */
    public function cartIndex(): array
    {
        $index = [];

        foreach ($this->lines as $i => $line) {
            if ($line['catalog_item_id'] !== null) {
                $index[$line['catalog_item_id']] ??= $i;
            }
        }

        return $index;
    }

    private function step(int $index, int $direction): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $unit = Unit::tryFrom($this->lines[$index]['unit']) ?? Unit::Piece;
        $quantity = round((float) $this->lines[$index]['quantity'] + $direction * $unit->step(), 3);

        if ($quantity <= 0) {
            $this->removeLine($index);

            return;
        }

        $this->lines[$index]['quantity'] = $quantity;
    }
}; ?>


@php($cart = $this->cartIndex())
<div class="mx-auto flex w-full max-w-2xl flex-col gap-4">
    <div>
        <flux:link :href="route('requests.index')" wire:navigate class="text-sm">← Все заявки</flux:link>
    </div>
    <flux:heading size="xl">Новая заявка</flux:heading>

    @if (! $this->section)
        <flux:text>Что заказываем?</flux:text>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($this->sections as $item)
                <button type="button" wire:click="chooseSection({{ $item->id }})"
                        class="flex h-20 items-center justify-center rounded-xl border border-zinc-200 bg-white text-lg font-semibold shadow-xs transition hover:border-accent hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                    {{ $item->name }}
                </button>
            @endforeach
        </div>
    @else
        <div class="flex items-center justify-between gap-2">
            <flux:badge color="emerald" size="lg">{{ $this->section->name }}</flux:badge>
            <flux:button size="sm" variant="ghost" wire:click="changeSection"
                         :wire:confirm="$lines ? 'Сменить раздел? Набранные позиции пропадут.' : null">
                Сменить раздел
            </flux:button>
        </div>

        {{-- Поиск: заметный и всегда сверху при прокрутке --}}
        <div class="sticky top-14 z-10 -mx-4 bg-zinc-50/95 px-4 py-2 backdrop-blur lg:top-0 dark:bg-zinc-900/95">
            <label class="flex items-center gap-2 rounded-xl border-2 border-zinc-300 bg-white px-3 shadow-xs focus-within:border-accent dark:border-zinc-600 dark:bg-zinc-800">
                <flux:icon.magnifying-glass class="size-5 shrink-0 text-zinc-400" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Найти: молоко, мука, сливки…"
                       class="h-12 w-full bg-transparent text-base outline-none placeholder:text-zinc-400" aria-label="Поиск по справочнику">
            </label>
        </div>

        {{-- Справочник: категория остаётся открытой, пока её не закроют --}}
        <section class="flex flex-col gap-2">
            @forelse ($this->categories as $category)
                <div x-data="{ open: false }" wire:key="category-{{ $category->id }}"
                     class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <button type="button" @click="open = ! open" class="flex w-full items-center justify-between gap-2 p-3 text-start font-medium">
                        <span>
                            {{ $category->name }}
                            <span class="text-sm font-normal text-zinc-500">{{ $category->items->count() }}</span>
                            @php($picked = $category->items->filter(fn ($item) => isset($cart[$item->id]))->count())
                            @if ($picked)
                                <flux:badge size="sm" color="emerald" class="ms-1">в заявке: {{ $picked }}</flux:badge>
                            @endif
                        </span>
                        <flux:icon.chevron-down class="size-5 text-zinc-400 transition" x-bind:class="(open || $wire.search.trim() !== '') && 'rotate-180'" />
                    </button>
                    <div x-show="open || $wire.search.trim() !== ''" x-cloak>
                        @foreach ($category->items as $item)
                            @php($index = $cart[$item->id] ?? null)
                            <div wire:key="item-{{ $item->id }}"
                                 class="flex min-h-12 items-center justify-between gap-2 border-t border-zinc-100 px-3 py-1.5 dark:border-zinc-700 {{ $index !== null ? 'bg-emerald-50 dark:bg-emerald-950/40' : '' }}">
                                <button type="button" wire:click="addItem({{ $item->id }})" class="min-w-0 flex-1 py-1 text-start">
                                    <span class="{{ $index !== null ? 'font-medium' : '' }}">{{ $item->name }}</span>
                                    <span class="text-sm text-zinc-500">{{ $item->unit->getLabel() }}</span>
                                    @isset($this->alreadyOrdered[$item->id])
                                        <span class="block text-xs text-amber-700 dark:text-amber-400">уже заказано — {{ $this->alreadyOrdered[$item->id] }}</span>
                                    @endisset
                                </button>
                                @if ($index !== null)
                                    <div class="flex shrink-0 items-center gap-1">
                                        <flux:button size="sm" icon="minus" wire:click="decrement({{ $index }})" aria-label="Меньше" />
                                        <span class="min-w-14 text-center text-sm font-semibold tabular-nums text-emerald-800 dark:text-emerald-300">
                                            {{ \App\Models\PurchaseRequestItem::formatQuantity((float) $lines[$index]['quantity']) }} {{ \App\Enums\Unit::from($lines[$index]['unit'])->getLabel() }}
                                        </span>
                                        <flux:button size="sm" icon="plus" wire:click="increment({{ $index }})" aria-label="Больше" />
                                    </div>
                                @else
                                    <flux:button size="sm" variant="filled" icon="plus" wire:click="addItem({{ $item->id }})" aria-label="Добавить {{ $item->name }}" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <flux:text>{{ $search !== '' ? 'Ничего не нашлось — добавьте свою позицию ниже.' : 'В справочнике раздела пока пусто — добавьте свою позицию ниже.' }}</flux:text>
            @endforelse
        </section>

        {{-- Своя позиция --}}
        <x-card class="flex flex-col gap-2">
            <flux:heading>Нет в списке? Добавьте своё</flux:heading>
            <div class="flex gap-2">
                <flux:input wire:model="customName" placeholder="Что нужно" class="flex-1" wire:keydown.enter="addCustom" />
                <select wire:model="customUnit" aria-label="Единица"
                        class="h-10 rounded-lg border border-zinc-300 bg-white px-2 dark:border-zinc-600 dark:bg-zinc-900">
                    @foreach (\App\Enums\Unit::options() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <flux:button wire:click="addCustom" icon="plus" aria-label="Добавить" />
            </div>
            @error('customName') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
        </x-card>

        {{-- Итог заявки --}}
        @if ($lines)
            <x-card class="flex flex-col gap-2 border-accent/40">
                <flux:heading>Ваша заявка: {{ count($lines) }} {{ trans_choice('позиция|позиции|позиций', count($lines)) }}</flux:heading>
                @foreach ($lines as $index => $line)
                    <div wire:key="line-{{ $index }}-{{ $line['catalog_item_id'] ?? $line['name'] }}" class="flex flex-col gap-2 border-b border-zinc-100 pb-2 last:border-0 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-medium">{{ $line['name'] }}</div>
                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeLine({{ $index }})" aria-label="Убрать" />
                        </div>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" icon="minus" wire:click="decrement({{ $index }})" aria-label="Меньше" />
                            <input type="text" inputmode="decimal" wire:model.blur="lines.{{ $index }}.quantity"
                                   class="h-10 w-20 rounded-lg border border-zinc-300 bg-white text-center dark:border-zinc-600 dark:bg-zinc-900"
                                   aria-label="Количество" />
                            <flux:button size="sm" icon="plus" wire:click="increment({{ $index }})" aria-label="Больше" />
                            <select wire:model.live="lines.{{ $index }}.unit" aria-label="Единица"
                                    class="h-10 rounded-lg border border-zinc-300 bg-white px-2 dark:border-zinc-600 dark:bg-zinc-900">
                                @foreach (\App\Enums\Unit::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error("lines.{$index}.quantity") <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                @endforeach
            </x-card>
        @endif

        {{-- Подробности --}}
        <x-card class="flex flex-col gap-4">
            <flux:input type="date" wire:model="neededBy" label="Нужно к (необязательно)" min="{{ now()->toDateString() }}" />
            <flux:switch wire:model="isUrgent" label="Срочно" />
            <flux:textarea wire:model="comment" label="Комментарий (необязательно)" rows="2" />
        </x-card>

        @error('lines')
            <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
        @enderror

        {{-- Кнопка отправки всегда на виду (на телефоне — над нижними вкладками) --}}
        <div class="sticky bottom-20 z-10 lg:bottom-4">
            <flux:button variant="primary" wire:click="submit" class="h-12 w-full shadow-lg" :disabled="! $lines">
                @if ($lines)
                    Отправить заявку · {{ count($lines) }} {{ trans_choice('позиция|позиции|позиций', count($lines)) }}
                @else
                    Выберите позиции
                @endif
            </flux:button>
        </div>
    @endif
</div>
