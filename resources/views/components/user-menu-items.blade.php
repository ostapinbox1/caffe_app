@php($user = auth()->user())
<flux:menu>
    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
        <flux:avatar :name="$user->name" :initials="$user->initials()" :src="$user->avatarUrl()" />
        <div class="grid flex-1 text-start text-sm leading-tight">
            <flux:heading class="truncate">{{ $user->name }}</flux:heading>
            <flux:text class="truncate">{{ $user->position?->name ?? $user->role->getLabel() }}</flux:text>
        </div>
    </div>
    <flux:menu.separator />
    <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>Профиль</flux:menu.item>
    <flux:menu.item :href="route('appearance.edit')" icon="swatch" wire:navigate>Тема оформления</flux:menu.item>
    <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>Пароль и защита</flux:menu.item>
    @if ($user->isManager())
        <flux:menu.item href="/admin" icon="cog-6-tooth">Настройки кофейни</flux:menu.item>
    @endif
    <flux:menu.separator />
    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
            Выйти
        </flux:menu.item>
    </form>
</flux:menu>
