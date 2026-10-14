<x-layouts::app.sidebar :title="$title ?? null">
    {{-- Снизу отступ под вкладки на телефоне --}}
    <flux:main class="pb-28 lg:pb-8">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
