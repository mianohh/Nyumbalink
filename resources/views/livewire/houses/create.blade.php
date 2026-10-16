<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <flux:heading level="2">{{ __('Add House') }}</flux:heading>

    <form wire:submit="save" class="max-w-xl space-y-4">
        <flux:input wire:model="house_number" :label="__('House Number')" type="text" required autofocus />

        <flux:input wire:model="location" :label="__('Location')" type="text" required />

        <flux:input wire:model="rent_amount" :label="__('Monthly Rent (KES)')" type="number" step="0.01" min="0" required />

        <flux:select wire:model="status" :label="__('Status')" required>
            <option value="available">{{ __('Available') }}</option>
            <option value="maintenance">{{ __('Maintenance') }}</option>
        </flux:select>

        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

        <flux:input wire:model="image" :label="__('Photo (optional, max 5 MB)')" type="file" accept="image/*" />

        @if ($image)
            <img src="{{ $image->temporaryUrl() }}" class="h-32 rounded-lg object-cover" alt="" />
        @endif

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button :href="route('houses.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
