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

<div class="mx-auto flex w-full max-w-2xl flex-col gap-5 pb-24">
    <flux:heading size="xl">Новая заявка</flux:heading>

    @if (! $this->section)
        <flux:text>Что заказываем?</flux:text>
        <div class="grid gap-3">
            @foreach ($this->sections as $item)
                <flux:button wire:click="chooseSection({{ $item->id }})" class="h-16 text-lg">
                    {{ $item->name }}
                </flux:button>
            @endforeach
        </div>
    @else
        <div class="flex items-center justify-between gap-2">
            <flux:heading size="lg">{{ $this->section->name }}</flux:heading>
            <flux:button size="sm" variant="ghost" wire:click="changeSection"
                         :wire:confirm="$lines ? 'Сменить раздел? Набранные позиции пропадут.' : null">
                Сменить раздел
            </flux:button>
        </div>

        {{-- Набранные позиции --}}
        @if ($lines)
            <section class="flex flex-col gap-2 rounded-lg border border-accent/40 p-3">
                <flux:heading>В заявке: {{ count($lines) }}</flux:heading>
                @foreach ($lines as $index => $line)
                    <div wire:key="line-{{ $index }}-{{ $line['catalog_item_id'] ?? $line['name'] }}" class="flex flex-col gap-2 border-b border-zinc-200 pb-2 last:border-0 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-2">
                            <div class="font-medium">{{ $line['name'] }}</div>
                            <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeLine({{ $index }})" aria-label="Убрать" />
                        </div>
                        @if ($line['catalog_item_id'] && isset($this->alreadyOrdered[$line['catalog_item_id']]))
                            <div class="text-xs text-amber-700 dark:text-amber-400">Уже заказано — {{ $this->alreadyOrdered[$line['catalog_item_id']] }}</div>
                        @endif
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" icon="minus" wire:click="decrement({{ $index }})" aria-label="Меньше" class="min-w-10" />
                            <input type="text" inputmode="decimal" wire:model.blur="lines.{{ $index }}.quantity"
                                   class="h-10 w-20 rounded-lg border border-zinc-300 bg-white text-center dark:border-zinc-600 dark:bg-zinc-900"
                                   aria-label="Количество" />
                            <flux:button size="sm" icon="plus" wire:click="increment({{ $index }})" aria-label="Больше" class="min-w-10" />
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
            </section>
        @endif

        {{-- Справочник --}}
        <section class="flex flex-col gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Найти позицию" clearable />

            @forelse ($this->categories as $category)
                <details wire:key="category-{{ $category->id }}" @if ($search !== '') open @endif
                         class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <summary class="cursor-pointer select-none p-3 font-medium">
                        {{ $category->name }} <span class="text-sm text-zinc-500">({{ $category->items->count() }})</span>
                    </summary>
                    <div class="flex flex-col">
                        @foreach ($category->items as $item)
                            @php($inCart = collect($lines)->firstWhere('catalog_item_id', $item->id))
                            <button type="button" wire:key="item-{{ $item->id }}" wire:click="addItem({{ $item->id }})"
                                    class="flex min-h-11 items-center justify-between gap-2 border-t border-zinc-100 px-3 py-2 text-start hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-700/40 {{ $inCart ? 'bg-accent/5' : '' }}">
                                <span>
                                    {{ $item->name }} <span class="text-sm text-zinc-500">{{ $item->unit->getLabel() }}</span>
                                    @isset($this->alreadyOrdered[$item->id])
                                        <span class="block text-xs text-amber-700 dark:text-amber-400">уже заказано — {{ $this->alreadyOrdered[$item->id] }}</span>
                                    @endisset
                                </span>
                                @if ($inCart)
                                    <span class="shrink-0 text-sm font-semibold text-accent-content">
                                        {{ \App\Models\PurchaseRequestItem::formatQuantity((float) $inCart['quantity']) }} {{ \App\Enums\Unit::from($inCart['unit'])->getLabel() }}
                                    </span>
                                @else
                                    <flux:icon.plus class="size-5 shrink-0 text-zinc-400" />
                                @endif
                            </button>
                        @endforeach
                    </div>
                </details>
            @empty
                <flux:text>{{ $search !== '' ? 'Ничего не нашлось — добавьте свою позицию ниже.' : 'В справочнике раздела пока пусто — добавьте свою позицию ниже.' }}</flux:text>
            @endforelse
        </section>

        {{-- Своя позиция --}}
        <section class="flex flex-col gap-2">
            <flux:heading>Нет в списке?</flux:heading>
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
        </section>

        {{-- Подробности --}}
        <section class="flex flex-col gap-4">
            <flux:input type="date" wire:model="neededBy" label="Нужно к (необязательно)" min="{{ now()->toDateString() }}" />
            <flux:switch wire:model="isUrgent" label="Срочно" />
            <flux:textarea wire:model="comment" label="Комментарий (необязательно)" rows="2" />
        </section>

        @error('lines')
            <flux:callout variant="danger" icon="exclamation-triangle">{{ $message }}</flux:callout>
        @enderror

        <div class="fixed inset-x-0 bottom-0 z-10 border-t border-zinc-200 bg-white/95 p-3 backdrop-blur lg:static lg:border-0 lg:bg-transparent lg:p-0 dark:border-zinc-700 dark:bg-zinc-800/95">
            <div class="mx-auto max-w-2xl">
                <flux:button variant="primary" wire:click="submit" class="h-12 w-full" :disabled="! $lines">
                    Отправить заявку @if ($lines) ({{ count($lines) }}) @endif
                </flux:button>
            </div>
        </div>
    @endif
</div>
