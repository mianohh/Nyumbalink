<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <flux:heading level="2">{{ __('Edit House') }}</flux:heading>

    <form wire:submit="update" class="max-w-xl space-y-4">
        <flux:input wire:model="house_number" :label="__('House Number')" type="text" required />

        <flux:input wire:model="location" :label="__('Location')" type="text" required />

        <flux:input wire:model="rent_amount" :label="__('Monthly Rent (KES)')" type="number" step="0.01" min="0" required />

        <flux:select wire:model="status" :label="__('Status')" required :disabled="$house->tenants()->exists()">
            <option value="available">{{ __('Available') }}</option>
            <option value="occupied">{{ __('Occupied') }}</option>
            <option value="maintenance">{{ __('Maintenance') }}</option>
        </flux:select>

        @if ($house->tenants()->exists())
            <flux:text class="text-sm text-zinc-500">
                {{ __('Occupied status is managed automatically by tenant assignment.') }}
            </flux:text>
        @endif

        <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

        <div class="space-y-2">
            @if ($house->image && ! $removeImage)
                <img src="{{ Storage::url($house->image) }}" class="h-32 rounded-lg object-cover" alt="" />
                <flux:button size="sm" variant="danger" wire:click="$set('removeImage', true)">
                    {{ __('Remove photo') }}
                </flux:button>
            @endif

            <flux:input wire:model="image" :label="__('Replace photo (max 5 MB)')" type="file" accept="image/*" />
        </div>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Update') }}</flux:button>
            <flux:button :href="route('houses.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @can('delete', $house)
        <div class="mt-8 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:heading level="3" class="text-red-600">{{ __('Danger Zone') }}</flux:heading>
            <flux:text>{{ __('Deleting a house removes it permanently. Tenants will be unassigned.') }}</flux:text>
            <flux:button class="mt-3" variant="danger" wire:click="delete" wire:confirm="Delete this house permanently?">
                {{ __('Delete House') }}
            </flux:button>
        </div>
    @endcan
</div>
