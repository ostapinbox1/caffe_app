{{-- Админка — только настройки; вся ежедневная работа в кабинете. --}}
<div style="margin-bottom: 1rem; padding: 0 0.5rem">
    <x-filament::button tag="a" :href="route('dashboard')" icon="heroicon-o-arrow-left" color="primary" style="width: 100%">
        Вернуться в кабинет
    </x-filament::button>
    <p style="margin-top: 0.5rem; padding: 0 0.25rem; font-size: 0.75rem; opacity: 0.6">
        Здесь настройки. Заявки, смены и чек-листы — в кабинете.
    </p>
</div>
