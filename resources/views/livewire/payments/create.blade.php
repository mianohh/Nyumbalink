<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <flux:heading level="2">{{ __('Record Payment') }}</flux:heading>

    <form wire:submit="save" class="max-w-xl space-y-4">
        <flux:select wire:model.live="tenant_id" :label="__('Tenant')" required>
            <option value="">{{ __('Select a tenant…') }}</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}">
                    {{ $tenant->name }} — {{ $tenant->house?->house_number ?? __('unassigned') }}
                </option>
            @endforeach
        </flux:select>

        @if ($preview)
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading level="4" class="mb-2">{{ __('Balance preview') }}</flux:heading>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-zinc-500">{{ __('House') }}</dt>
                    <dd>{{ $preview['house'] ?? '—' }}</dd>
                    <dt class="text-zinc-500">{{ __('Monthly rent') }}</dt>
                    <dd>{{ format_currency($preview['rent']) }}</dd>
                    <dt class="text-zinc-500">{{ __('Already paid this month') }}</dt>
                    <dd class="text-green-600">{{ format_currency($preview['paid']) }}</dd>
                    <dt class="text-zinc-500">{{ __('Balance due') }}</dt>
                    <dd class="font-semibold {{ $preview['balance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ format_currency($preview['balance']) }}
                    </dd>
                </dl>
            </div>
        @endif

        <flux:input wire:model="amount_paid" :label="__('Amount Paid (KES)')" type="number" step="0.01" min="0.01" required />

        <flux:input wire:model="payment_date" :label="__('Payment Date')" type="date" required />

        <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Record Payment') }}</flux:button>
            <flux:button :href="route('payments.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</div>
