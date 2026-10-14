@php
    $user = auth()->user();
    $badges = \App\Support\CabinetBadges::for($user);
    $cafe = $user->cafe;
    $nav = [
        ['route' => 'dashboard', 'match' => ['dashboard'], 'icon' => 'home', 'label' => 'Главная', 'badge' => null],
        ['route' => 'requests.index', 'match' => ['requests.*'], 'icon' => 'shopping-cart', 'label' => 'Заявки', 'badge' => $badges['requests'] ?: null],
        ['route' => 'checklists.index', 'match' => ['checklists.*'], 'icon' => 'clipboard-document-check', 'label' => 'Чек-листы', 'badge' => $badges['checklists'] ?: null],
    ];
    if ($user->isManager()) {
        $nav[] = ['route' => 'team', 'match' => ['team'], 'icon' => 'user-group', 'label' => 'Смены', 'badge' => null];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        {{-- Компьютер: боковое меню --}}
        <flux:sidebar sticky class="hidden border-e border-zinc-200 bg-white lg:flex dark:border-zinc-800 dark:bg-zinc-950">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex flex-col gap-2 px-2 pt-1">
                <x-brand-logo class="h-12" />
                @if ($cafe)
                    <span class="px-1 text-xs leading-tight text-zinc-500">{{ $cafe->name }}@if ($cafe->address), {{ $cafe->address }}@endif</span>
                @endif
            </a>

            <flux:sidebar.nav>
                @foreach ($nav as $item)
                    <flux:sidebar.item :icon="$item['icon']" :href="route($item['route'])" :current="request()->routeIs(...$item['match'])" :badge="$item['badge']" wire:navigate>
                        {{ $item['label'] }}
                    </flux:sidebar.item>
                @endforeach
                <flux:sidebar.item icon="bell" :href="route('notifications')" :current="request()->routeIs('notifications')" :badge="$badges['notifications'] ?: null" wire:navigate>
                    Уведомления
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:spacer />

            @if ($user->isManager())
                <a href="/admin" class="mx-1 flex items-center gap-3 rounded-lg border border-dashed border-zinc-300 px-3 py-2.5 text-sm text-zinc-600 transition hover:border-accent hover:text-accent-content dark:border-zinc-700 dark:text-zinc-400">
                    <flux:icon.cog-6-tooth class="size-5" />
                    <span class="leading-tight">
                        <span class="block font-medium">Настройки</span>
                        <span class="block text-xs text-zinc-500">сотрудники, чек-листы, справочник</span>
                    </span>
                </a>
            @endif

            <x-desktop-user-menu />
        </flux:sidebar>

        {{-- Телефон: шапка --}}
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-zinc-200 bg-white/95 px-4 py-2 backdrop-blur lg:hidden dark:border-zinc-800 dark:bg-zinc-950/95">
            <a href="{{ route('dashboard') }}" wire:navigate aria-label="Главная">
                <x-brand-logo class="h-9" />
            </a>
            <div class="flex items-center gap-1">
                <a href="{{ route('notifications') }}" wire:navigate class="relative rounded-full p-2 text-zinc-600 dark:text-zinc-300" aria-label="Уведомления">
                    <flux:icon.bell class="size-6" />
                    @if ($badges['notifications'])
                        <span class="absolute right-0.5 top-0.5 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-semibold text-white">{{ $badges['notifications'] }}</span>
                    @endif
                </a>
                <x-mobile-user-menu />
            </div>
        </header>

        <flux:main class="pb-28 lg:pb-8">
            {{ $slot }}
        </flux:main>

        {{-- Телефон: нижние вкладки --}}
        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden dark:border-zinc-800 dark:bg-zinc-950/95">
            <div class="mx-auto grid max-w-lg" style="grid-template-columns: repeat({{ count($nav) }}, minmax(0, 1fr))">
                @foreach ($nav as $item)
                    @php($active = request()->routeIs(...$item['match']))
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="relative flex flex-col items-center gap-0.5 py-2 text-xs {{ $active ? 'font-semibold text-accent-content' : 'text-zinc-500' }}">
                        <flux:icon :name="$item['icon']" :variant="$active ? 'solid' : 'outline'" class="size-6" />
                        {{ $item['label'] }}
                        @if ($item['badge'])
                            <span class="absolute left-1/2 top-1 ms-2 min-w-5 rounded-full bg-red-600 px-1 text-center text-[11px] font-semibold text-white">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </nav>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
