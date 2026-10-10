<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <flux:heading level="2">{{ __('Tenants') }}</flux:heading>

        <flux:button :href="route('tenants.create')" variant="primary" wire:navigate>
            {{ __('Add Tenant') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search name, phone or ID number…')" />

    <flux:table :striped="true">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Contact</flux:table.column>
            <flux:table.column>ID Number</flux:table.column>
            <flux:table.column>House</flux:table.column>
            <flux:table.column class="text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($tenants as $tenant)
                <flux:table.row :key="$tenant->id">
                    <flux:table.cell>{{ $tenant->name }}</flux:table.cell>
                    <flux:table.cell>{{ $tenant->contact }}</flux:table.cell>
                    <flux:table.cell>{{ $tenant->id_number }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($tenant->house)
                            {{ $tenant->house->house_number }}
                        @else
                            <flux:badge size="sm" color="zinc">{{ __('Unassigned') }}</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="text-end">
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" :href="route('tenants.show', $tenant)" wire:navigate variant="primary">
                                {{ __('View') }}
                            </flux:button>
                            <flux:button size="sm" :href="route('tenants.edit', $tenant)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No tenants found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $tenants->links() }}
</div>
