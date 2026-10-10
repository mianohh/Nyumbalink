<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <flux:heading level="2">{{ __('Add Tenant') }}</flux:heading>

    <form wire:submit="save" class="max-w-xl space-y-4">
        <flux:input wire:model="name" :label="__('Full Name')" type="text" required autofocus />

        <flux:input wire:model="contact" :label="__('Phone Number')" type="text" placeholder="+2547XXXXXXXX" required />

        <flux:input wire:model="id_number" :label="__('ID Number')" type="text" required />

        <flux:select wire:model="house_id" :label="__('Assign House (optional)')">
            <option value="">{{ __('No house — assign later') }}</option>
            @foreach ($houses as $house)
                <option value="{{ $house->id }}" @disabled($house->status->value === 'occupied')>
                    {{ $house->house_number }} — {{ $house->location }} ({{ format_currency($house->rent_amount) }})
                    {{ $house->status->value === 'occupied' ? '· '.__('occupied') : '' }}
                </option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('tenants.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
