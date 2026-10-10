<div class="flex h-full w-full min-w-0 flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:heading level="2">{{ __('Payment') }} {{ $payment->receipt_number }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('payments.receipt', $payment)" variant="primary" target="_blank">
                {{ __('View Receipt') }}
            </flux:button>
            <flux:button :href="route('payments.receipt', $payment).'?download=1'" target="_blank">
                {{ __('Download PDF') }}
            </flux:button>
            <flux:button :href="route('payments.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid min-w-0 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading level="3" class="mb-3">{{ __('Payment Details') }}</flux:heading>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Receipt Number') }}</dt>
                    <dd class="font-mono">{{ $payment->receipt_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Amount') }}</dt>
                    <dd class="font-semibold">{{ format_currency($payment->amount_paid) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Payment Date') }}</dt>
                    <dd>{{ format_date($payment->payment_date) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">{{ __('Recorded') }}</dt>
                    <dd>{{ format_date($payment->created_at) }}</dd>
                </div>
                @if ($payment->notes)
                    <div>
                        <dt class="text-zinc-500">{{ __('Notes') }}</dt>
                        <dd>{{ $payment->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading level="3" class="mb-3">{{ __('Tenant') }}</flux:heading>
            <flux:heading level="4">
                <flux:link :href="route('tenants.show', $payment->tenant_id)" wire:navigate>
                    {{ $payment->tenant->name }}
                </flux:link>
            </flux:heading>
            <flux:text>{{ $payment->tenant->contact }}</flux:text>
            <flux:text class="mt-2 block">
                {{ __('House') }}: {{ $payment->tenant->house?->house_number ?? '—' }}
            </flux:text>
        </div>
    </div>
</div>
