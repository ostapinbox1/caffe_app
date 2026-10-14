<?php

use App\Support\Avatar;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Профиль')] class extends Component {
    use WithFileUploads;

    public string $lastName = '';

    public string $firstName = '';

    public string $middleName = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $avatar = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->lastName = (string) $user->last_name;
        $this->firstName = (string) ($user->first_name ?? $user->name);
        $this->middleName = (string) $user->middle_name;
    }

    /**
     * Имя меняет сам сотрудник; логин и роль — только управляющий.
     */
    public function updateProfileInformation(): void
    {
        $validated = $this->validate([
            'lastName' => ['nullable', 'string', 'max:100'],
            'firstName' => ['required', 'string', 'max:100'],
            'middleName' => ['nullable', 'string', 'max:100'],
        ], attributes: ['lastName' => 'фамилия', 'firstName' => 'имя', 'middleName' => 'отчество']);

        Auth::user()->update([
            'last_name' => trim($validated['lastName']) ?: null,
            'first_name' => trim($validated['firstName']),
            'middle_name' => trim($validated['middleName']) ?: null,
        ]);

        Flux::toast(variant: 'success', text: 'Профиль сохранён.');
    }

    /**
     * Фото выбрано — сразу сохраняем.
     */
    public function updatedAvatar(): void
    {
        $this->validate(['avatar' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192']], [
            'avatar.image' => 'Нужна картинка: JPG, PNG или WebP.',
            'avatar.mimes' => 'Нужна картинка: JPG, PNG или WebP.',
            'avatar.max' => 'Файл больше 8 МБ.',
        ]);

        Avatar::store($this->avatar, Auth::user());
        $this->avatar = null;

        Flux::toast(variant: 'success', text: 'Фото обновлено.');
    }

    public function removeAvatar(): void
    {
        Avatar::remove(Auth::user());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout heading="Профиль" subheading="Как вас видят коллеги">
        <div class="my-6 flex items-center gap-4">
            <flux:avatar size="xl" :name="auth()->user()->name" :initials="auth()->user()->initials()" :src="auth()->user()->avatarUrl()" />
            <div class="flex flex-col gap-2">
                <label class="inline-flex">
                    <input type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only">
                    <span class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium shadow-xs hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800">
                        <span wire:loading.remove wire:target="avatar">{{ auth()->user()->avatar_path ? 'Сменить фото' : 'Загрузить фото' }}</span>
                        <span wire:loading wire:target="avatar">Загружаем…</span>
                    </span>
                </label>
                @if (auth()->user()->avatar_path)
                    <flux:button size="xs" variant="ghost" wire:click="removeAvatar">Убрать фото</flux:button>
                @endif
                @error('avatar') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
            </div>
        </div>

        <form wire:submit="updateProfileInformation" class="flex w-full flex-col gap-5">
            <flux:input wire:model="lastName" label="Фамилия" autocomplete="family-name" />
            <flux:input wire:model="firstName" label="Имя" required autocomplete="given-name" />
            <flux:input wire:model="middleName" label="Отчество" autocomplete="additional-name" />

            <flux:input :value="auth()->user()->email" label="Логин" readonly disabled
                        description="Логин и должность меняет управляющий." />
            <flux:input :value="auth()->user()->position?->name ?? '—'" label="Должность" readonly disabled />

            <div>
                <flux:button variant="primary" type="submit" data-test="update-profile-button">Сохранить</flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
