<flux:dropdown position="top" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        :avatar="auth()->user()->avatarUrl()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <x-user-menu-items />
</flux:dropdown>
