<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <flux:heading level="2">{{ __('Payments') }}</flux:heading>

        <flux:button :href="route('payments.create')" variant="primary" wire:navigate>
            {{ __('Record Payment') }}
        </flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input class="sm:flex-1" wire:model.live.debounce.300ms="search" :placeholder="__('Search receipt or tenant…')" />
        <flux:input wire:model.live="date_from" type="date" :label="__('From')" class="sm:w-44" />
        <flux:input wire:model.live="date_to" type="date" :label="__('To')" class="sm:w-44" />
    </div>

    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:text class="text-zinc-500">{{ __('Total shown') }}</flux:text>
        <flux:heading level="3">{{ format_currency($total) }}</flux:heading>
    </div>

    <flux:table :striped="true">
        <flux:table.columns>
            <flux:table.column>Receipt</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Tenant</flux:table.column>
            <flux:table.column>House</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column class="text-end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($payments as $payment)
                <flux:table.row :key="$payment->id">
                    <flux:table.cell class="font-mono text-sm">{{ $payment->receipt_number }}</flux:table.cell>
                    <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:link :href="route('tenants.show', $payment->tenant_id)" wire:navigate>
                            {{ $payment->tenant->name }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $payment->tenant->house?->house_number ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-semibold">{{ format_currency($payment->amount_paid) }}</flux:table.cell>
                    <flux:table.cell class="text-end">
                        <flux:button size="sm" :href="route('payments.show', $payment)" wire:navigate>
                            {{ __('View') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __('No payments found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{ $payments->links() }}
</div>
