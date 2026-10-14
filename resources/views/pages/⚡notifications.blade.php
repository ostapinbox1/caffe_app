<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Уведомления')] class extends Component {
    use WithPagination;

    #[Computed]
    public function notifications()
    {
        return Auth::user()->notifications()->paginate(30);
    }

    /**
     * Отметить прочитанным и перейти к заявке.
     */
    public function open(string $id): void
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Только свои адреса: ссылка лежит в базе, но мало ли.
        if (is_string($url) && str_starts_with($url, url('/'))) {
            $this->redirect($url, navigate: ! str_contains($url, '/admin/'));
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }
}; ?>

<div class="mx-auto flex w-full max-w-2xl flex-col gap-4">
    <div class="flex items-center justify-between gap-2">
        <flux:heading size="xl">Уведомления</flux:heading>
        @if (auth()->user()->unreadNotifications()->exists())
            <flux:button size="sm" variant="ghost" wire:click="markAllAsRead">Прочитать все</flux:button>
        @endif
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($this->notifications as $notification)
            <button type="button" wire:key="n-{{ $notification->id }}" wire:click="open('{{ $notification->id }}')"
                    class="rounded-lg border p-3 text-start {{ $notification->read_at ? 'border-zinc-200 dark:border-zinc-700' : 'border-accent bg-accent/5' }}">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-medium">{{ $notification->data['title'] ?? '' }}</span>
                    <span class="shrink-0 text-xs text-zinc-500">{{ $notification->created_at->translatedFormat('j M, H:i') }}</span>
                </div>
                <div class="whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $notification->data['body'] ?? '' }}</div>
            </button>
        @empty
            <flux:text class="py-6 text-center">Уведомлений пока нет.</flux:text>
        @endforelse
    </div>

    {{ $this->notifications->links() }}
</div>
