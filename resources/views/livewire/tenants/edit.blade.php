<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <flux:heading level="2">{{ __('Edit Tenant') }}</flux:heading>

    <form wire:submit="update" class="max-w-xl space-y-4">
        <flux:input wire:model="name" :label="__('Full Name')" type="text" required />

        <flux:input wire:model="contact" :label="__('Phone Number')" type="text" required />

        <flux:input wire:model="id_number" :label="__('ID Number')" type="text" required />

        <flux:select wire:model="house_id" :label="__('House')">
            <option value="">{{ __('No house') }}</option>
            @foreach ($houses as $house)
                <option value="{{ $house->id }}">
                    {{ $house->house_number }} — {{ $house->location }}
                    {{ $house->status->value === 'occupied' && $house->id !== $tenant->house_id ? '· '.__('occupied') : '' }}
                </option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Update') }}</flux:button>
            <flux:button :href="route('tenants.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @can('delete', $tenant)
        <div class="mt-8 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:heading level="3" class="text-red-600">{{ __('Danger Zone') }}</flux:heading>
            <flux:text>{{ __('Tenants with recorded payments cannot be deleted.') }}</flux:text>
            <flux:button class="mt-3" variant="danger" wire:click="delete" wire:confirm="Delete this tenant permanently?">
                {{ __('Delete Tenant') }}
            </flux:button>
        </div>
    @endcan
</div>
