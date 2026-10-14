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
     * Отметить прочитанным и перейти по ссылке.
     */
    public function open(string $id): void
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Только свои адреса: ссылка лежит в базе, но мало ли.
        if (is_string($url) && str_starts_with($url, url('/'))) {
            $this->redirect($url, navigate: true);
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
            <flux:button size="sm" variant="ghost" icon="check" wire:click="markAllAsRead">Прочитать все</flux:button>
        @endif
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($this->notifications as $notification)
            @php($color = $notification->data['color'] ?? 'sky')
            @php($tone = match ($color) {
                'red' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
                'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                'emerald', 'green' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                'zinc' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
                default => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            })
            <button type="button" wire:key="n-{{ $notification->id }}" wire:click="open('{{ $notification->id }}')"
                    class="group flex items-start gap-3 rounded-xl border bg-white p-3 text-start shadow-xs transition hover:shadow-md dark:bg-zinc-800 {{ $notification->read_at ? 'border-zinc-200 dark:border-zinc-700' : 'border-accent ring-1 ring-accent/30' }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $tone }}">
                    <flux:icon :name="$notification->data['icon'] ?? 'bell'" class="size-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-start justify-between gap-2">
                        <span class="font-medium">{{ $notification->data['title'] ?? '' }}</span>
                        <span class="shrink-0 text-xs text-zinc-500">{{ $notification->created_at->translatedFormat('j M, H:i') }}</span>
                    </span>
                    <span class="block whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $notification->data['body'] ?? '' }}</span>
                    <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-accent-content group-hover:underline">Открыть <flux:icon.arrow-right variant="micro" class="size-3" /></span>
                </span>
            </button>
        @empty
            <x-card>
                <flux:text class="text-center">Уведомлений пока нет.</flux:text>
            </x-card>
        @endforelse
    </div>

    {{ $this->notifications->links() }}
</div>
