<?php

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Главная')] class extends Component {
    /**
     * Мои незакрытые заявки.
     */
    #[Computed]
    public function myOpenRequests()
    {
        return PurchaseRequest::query()
            ->open()
            ->where('author_id', Auth::id())
            ->with(['section', 'items', 'author'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function unreadNotifications()
    {
        return Auth::user()->unreadNotifications()->limit(5)->get();
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Здравствуйте, {{ Str::before(auth()->user()->name, ' ') }}!</flux:heading>
        @if (auth()->user()->position)
            <flux:text>{{ auth()->user()->position }}</flux:text>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3">
        <flux:button variant="primary" icon="plus" :href="route('requests.create')" wire:navigate class="h-14">
            Новая заявка
        </flux:button>
        <flux:button icon="shopping-cart" :href="route('requests.index')" wire:navigate class="h-14">
            Все заявки
        </flux:button>
    </div>

    @if ($this->unreadNotifications->isNotEmpty())
        <section class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Новые уведомления</flux:heading>
                <flux:link :href="route('notifications')" wire:navigate class="text-sm">Все</flux:link>
            </div>
            @foreach ($this->unreadNotifications as $notification)
                <a href="{{ route('notifications') }}" wire:navigate
                   class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="font-medium">{{ $notification->data['title'] ?? '' }}</div>
                    <div class="whitespace-pre-line text-sm text-zinc-500">{{ $notification->data['body'] ?? '' }}</div>
                </a>
            @endforeach
        </section>
    @endif

    <section class="flex flex-col gap-2">
        <flux:heading size="lg">Мои открытые заявки</flux:heading>
        @forelse ($this->myOpenRequests as $request)
            <x-request-card :request="$request" />
        @empty
            <flux:text>Открытых заявок нет.</flux:text>
        @endforelse
    </section>
</div>
