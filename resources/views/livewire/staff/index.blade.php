<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <flux:heading level="2">{{ __('Staff') }}</flux:heading>

        <flux:button :href="route('staff.create')" variant="primary" wire:navigate>
            {{ __('Add Staff') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search name or email…')" />

    <flux:table :striped="true">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column class="hidden sm:table-cell">Joined</flux:table.column>
            <flux:table.column class="text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($users as $user)
                <flux:table.row :key="$user->id">
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$user->isAdmin() ? 'amber' : 'zinc'" size="sm">
                            {{ $user->role->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell class="hidden sm:table-cell">{{ format_date($user->created_at) }}</flux:table.cell>
                    <flux:table.cell class="text-end">
                        @can('delete', $user)
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $user->id }})"
                                wire:confirm="Remove this staff member?">
                                {{ __('Delete') }}
                            </flux:button>
                        @endcan
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    {{ $users->links() }}
</div>
