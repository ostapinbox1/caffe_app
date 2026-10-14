<?php

use App\Enums\RequestStatus;
use App\Models\PurchaseRequest;
use App\Models\RequestSection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Заявки')] class extends Component {
    use WithPagination;

    /** open — все открытые, mine — мои, closed — закрытые за 30 дней */
    #[Url]
    public string $tab = 'open';

    #[Url]
    public string $section = '';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return RequestSection::query()->orderBy('sort')->get();
    }

    #[Computed]
    public function requests()
    {
        return PurchaseRequest::query()
            ->with(['section', 'author', 'items'])
            ->when($this->section !== '', fn ($query) => $query->where('request_section_id', (int) $this->section))
            ->when($this->tab === 'open', fn ($query) => $query->open())
            ->when($this->tab === 'mine', fn ($query) => $query->where('author_id', Auth::id()))
            ->when($this->tab === 'closed', fn ($query) => $query
                ->whereIn('status', [RequestStatus::Received, RequestStatus::Cancelled])
                ->where('status_changed_at', '>=', now()->subDays(30)))
            ->orderByDesc('is_urgent')
            ->latest()
            ->paginate(20);
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-4">
    <div class="flex items-center justify-between gap-2">
        <flux:heading size="xl">Заявки</flux:heading>
        <flux:button variant="primary" icon="plus" size="sm" :href="route('requests.create')" wire:navigate>
            Новая
        </flux:button>
    </div>

    <div class="flex gap-2 overflow-x-auto">
        @foreach (['open' => 'Открытые', 'mine' => 'Мои', 'closed' => 'Закрытые'] as $value => $label)
            <flux:button size="sm" :variant="$tab === $value ? 'primary' : 'ghost'" wire:click="$set('tab', '{{ $value }}')">
                {{ $label }}
            </flux:button>
        @endforeach
    </div>

    <flux:select wire:model.live="section" size="sm" aria-label="Раздел">
        <flux:select.option value="">Все разделы</flux:select.option>
        @foreach ($this->sections as $item)
            <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <div class="flex flex-col gap-2">
        @forelse ($this->requests as $request)
            <x-request-card :request="$request" wire:key="request-{{ $request->id }}" />
        @empty
            <flux:text class="py-6 text-center">Заявок нет.</flux:text>
        @endforelse
    </div>

    {{ $this->requests->links() }}
</div>
