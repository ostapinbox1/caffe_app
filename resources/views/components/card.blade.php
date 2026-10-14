{{-- Белая карточка на сером фоне кабинета. --}}
<div {{ $attributes->class('rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-800') }}>
    {{ $slot }}
</div>
