<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <flux:heading level="2">{{ __('Add Staff Member') }}</flux:heading>

    <form wire:submit="save" class="max-w-xl space-y-4">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

        <flux:input wire:model="email" :label="__('Email')" type="email" required />

        <flux:input wire:model="password" :label="__('Password')" type="password" required />

        <flux:select wire:model="role" :label="__('Role')" required>
            <option value="staff">{{ __('Staff') }}</option>
            <option value="admin">{{ __('Administrator') }}</option>
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create') }}</flux:button>
            <flux:button :href="route('staff.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
