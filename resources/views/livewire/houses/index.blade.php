<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <flux:heading level="2">{{ __('Houses') }}</flux:heading>

        <flux:button :href="route('houses.create')" variant="primary" wire:navigate>
            {{ __('Add House') }}
        </flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input class="sm:flex-1" wire:model.live.debounce.300ms="search" :placeholder="__('Search number or location…')" />

        <flux:select wire:model.live="status" class="sm:w-56">
            <option value="">{{ __('All statuses') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($houses as $house)
            <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                @if ($house->image)
                    <img src="{{ Storage::url($house->image) }}" alt="{{ $house->house_number }}" class="h-40 w-full object-cover" />
                @else
                    <div class="flex h-40 w-full items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="home" class="size-10 text-zinc-400" />
                    </div>
                @endif

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <div class="flex items-center justify-between">
                        <flux:heading level="3">{{ $house->house_number }}</flux:heading>

                        <flux:badge :color="$house->status->value === 'available' ? 'green' : ($house->status->value === 'occupied' ? 'amber' : 'red')" size="sm">
                            {{ $house->status->label() }}
                        </flux:badge>
                    </div>

                    <flux:text>{{ $house->location }}</flux:text>
                    <flux:text class="font-semibold">{{ format_currency($house->rent_amount) }} / month</flux:text>

                    <div class="mt-auto flex gap-2 pt-2">
                        <flux:button size="sm" :href="route('houses.show', $house)" wire:navigate variant="primary">
                            {{ __('View') }}
                        </flux:button>
                        <flux:button size="sm" :href="route('houses.edit', $house)" wire:navigate>
                            {{ __('Edit') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-zinc-500 sm:col-span-2 lg:col-span-3">{{ __('No houses found.') }}</p>
        @endforelse
    </div>

    {{ $houses->links() }}
</div>
