<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <flux:heading level="2">{{ $house->house_number }}</flux:heading>

        <div class="flex gap-2">
            <flux:button :href="route('houses.edit', $house)" wire:navigate>{{ __('Edit') }}</flux:button>
            <flux:button :href="route('houses.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-zinc-500">{{ __('Location') }}</flux:text>
                        <flux:heading level="3">{{ $house->location }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500">{{ __('Monthly Rent') }}</flux:text>
                        <flux:heading level="3">{{ format_currency($house->rent_amount) }}</flux:heading>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500">{{ __('Status') }}</flux:text>
                        <div class="mt-1">
                            <flux:badge :color="$house->status->value === 'available' ? 'green' : ($house->status->value === 'occupied' ? 'amber' : 'red')">
                                {{ $house->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                    @if ($house->description)
                        <div>
                            <flux:text class="text-zinc-500">{{ __('Description') }}</flux:text>
                            <flux:text>{{ $house->description }}</flux:text>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-3">{{ __('Rental Agreements') }}</flux:heading>

                @if ($house->agreements->isEmpty())
                    <flux:text class="text-zinc-500">{{ __('No agreements for this house.') }}</flux:text>
                @else
                    <flux:table :striped="true">
                        <flux:table.columns>
                            <flux:table.column>Tenant</flux:table.column>
                            <flux:table.column>Period</flux:table.column>
                            <flux:table.column>Rent</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($house->agreements as $agreement)
                                <flux:table.row :key="$agreement->id">
                                    <flux:table.cell>{{ $agreement->tenant?->name }}</flux:table.cell>
                                    <flux:table.cell>
                                        {{ format_date($agreement->start_date) }} – {{ format_date($agreement->end_date) }}
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_currency($agreement->monthly_rent) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="$agreement->status->value === 'active' ? 'green' : 'zinc'">
                                            {{ $agreement->status->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-3">{{ __('Current Tenant') }}</flux:heading>

                @php $current = $house->tenants->first(); @endphp

                @if ($current)
                    <flux:heading level="4">{{ $current->name }}</flux:heading>
                    <flux:text>{{ $current->contact }}</flux:text>

                    <div class="mt-3 flex gap-2">
                        <flux:button size="sm" :href="route('tenants.show', $current)" wire:navigate>
                            {{ __('View Tenant') }}
                        </flux:button>

                        @can('update', $house)
                            <flux:button size="sm" variant="danger" wire:click="unassign"
                                wire:confirm="Unassign this tenant from the house?">
                                {{ __('Unassign') }}
                            </flux:button>
                        @endcan
                    </div>
                @else
                    <flux:text class="text-zinc-500">{{ __('No tenant assigned.') }}</flux:text>

                    @can('update', $house)
                        @if ($availableTenants->isNotEmpty())
                            <div class="mt-3 space-y-2">
                                <flux:select wire:model="tenantToAssign" :label="__('Assign tenant')">
                                    <option value="">{{ __('Select a tenant…') }}</option>
                                    @foreach ($availableTenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->name }} ({{ $tenant->contact }})</option>
                                    @endforeach
                                </flux:select>

                                <flux:button variant="primary" size="sm" wire:click="assign" :disabled="!$tenantToAssign">
                                    {{ __('Assign') }}
                                </flux:button>
                            </div>
                        @else
                            <flux:text class="mt-2 block text-sm text-zinc-500">
                                {{ __('All tenants are already assigned. Create an unassigned tenant first.') }}
                            </flux:text>
                        @endif
                    @endcan
                @endif
            </div>

            @if ($house->image)
                <img src="{{ Storage::url($house->image) }}" class="rounded-xl border border-zinc-200 dark:border-zinc-700" alt="{{ $house->house_number }}" />
            @endif
        </div>
    </div>
</div>
