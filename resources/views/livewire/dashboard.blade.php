<div class="flex h-full w-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Total Tenants') }}</flux:text>
            <flux:heading level="2">{{ $totalTenants }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Available Houses') }}</flux:text>
            <flux:heading level="2" class="text-green-600">{{ $availableHouses }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Occupied Houses') }}</flux:text>
            <flux:heading level="2" class="text-amber-600">{{ $occupiedHouses }}</flux:heading>
        </div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:text class="text-zinc-500">{{ __('Revenue This Month') }}</flux:text>
            <flux:heading level="2">{{ format_currency($monthRevenue) }}</flux:heading>
        </div>
    </div>

    <div class="grid min-w-0 gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 lg:col-span-2">
            <flux:heading level="3" class="mb-3">{{ __('Revenue — Last 6 Months') }}</flux:heading>
            <div class="h-64">
                <canvas data-chart="bar"
                    data-labels='@json($revenueLabels)'
                    data-values='@json($revenueValues)'></canvas>
            </div>
        </div>
        <div class="min-w-0 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading level="3" class="mb-3">{{ __('House Status') }}</flux:heading>
            <div class="h-64">
                <canvas data-chart="doughnut"
                    data-labels='@json($statusLabels)'
                    data-values='@json($statusValues)'></canvas>
            </div>
        </div>
    </div>

    <div class="grid min-w-0 gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 lg:col-span-2">
            <flux:heading level="3" class="mb-3">{{ __('Recent Payments') }}</flux:heading>

            <flux:table :striped="true">
                <flux:table.columns>
                    <flux:table.column>Receipt</flux:table.column>
                    <flux:table.column>Date</flux:table.column>
                    <flux:table.column>Tenant</flux:table.column>
                    <flux:table.column>Amount</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($recentPayments as $payment)
                        <flux:table.row :key="$payment->id">
                            <flux:table.cell class="font-mono text-sm">
                                <flux:link :href="route('payments.show', $payment)" wire:navigate>
                                    {{ $payment->receipt_number }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                            <flux:table.cell>{{ $payment->tenant->name }}</flux:table.cell>
                            <flux:table.cell class="font-semibold">{{ format_currency($payment->amount_paid) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">{{ __('No payments yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-2">{{ __('Total Revenue') }}</flux:heading>
                <flux:heading level="2">{{ format_currency($totalRevenue) }}</flux:heading>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-3">{{ __('Quick Actions') }}</flux:heading>
                <div class="flex flex-col gap-2">
                    <flux:button :href="route('payments.create')" variant="primary" wire:navigate>
                        {{ __('Record Payment') }}
                    </flux:button>
                    <flux:button :href="route('tenants.create')" wire:navigate>
                        {{ __('Add Tenant') }}
                    </flux:button>
                    <flux:button :href="route('houses.create')" wire:navigate>
                        {{ __('Add House') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
</div>
