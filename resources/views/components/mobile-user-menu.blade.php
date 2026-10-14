<flux:dropdown position="bottom" align="end">
    <flux:profile
        :initials="auth()->user()->initials()"
        :avatar="auth()->user()->avatarUrl()"
        :chevron="false"
    />

    <x-user-menu-items />
</flux:dropdown>
