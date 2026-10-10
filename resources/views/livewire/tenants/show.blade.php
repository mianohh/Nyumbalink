<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
    <div class="flex items-center justify-between">
        <flux:heading level="2">{{ $tenant->name }}</flux:heading>

        <div class="flex gap-2">
            <flux:button :href="route('payments.create', ['tenant_id' => $tenant->id])" variant="primary" wire:navigate>
                {{ __('Record Payment') }}
            </flux:button>
            <flux:button :href="route('tenants.edit', $tenant)" wire:navigate>{{ __('Edit') }}</flux:button>
            <flux:button :href="route('tenants.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-3">{{ __('Details') }}</flux:heading>

                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Phone') }}</dt>
                        <dd>{{ $tenant->contact }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('ID Number') }}</dt>
                        <dd>{{ $tenant->id_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('House') }}</dt>
                        <dd>
                            @if ($tenant->house)
                                <flux:link :href="route('houses.show', $tenant->house)" wire:navigate>
                                    {{ $tenant->house->house_number }} — {{ $tenant->house->location }}
                                </flux:link>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Unassigned') }}</flux:badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-2">{{ __('This Month') }}</flux:heading>

                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Rent due') }}</dt>
                        <dd class="font-semibold">
                            {{ format_currency($tenant->agreements()->where('status', 'active')->latest('start_date')->value('monthly_rent') ?? $tenant->house?->rent_amount ?? 0) }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500">{{ __('Paid') }}</dt>
                        <dd class="font-semibold text-green-600">{{ format_currency($tenant->totalPaidForMonth()) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                        <dt class="text-zinc-500">{{ __('Balance') }}</dt>
                        @php $balance = $tenant->balanceForMonth(); @endphp
                        <dd class="font-semibold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ format_currency($balance) }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading level="3">{{ __('Rental Agreements') }}</flux:heading>

                    @can('create', \App\Models\RentalAgreement::class)
                        <flux:button size="sm" wire:click="$toggle('showAgreementForm')">
                            {{ $showAgreementForm ? __('Cancel') : __('New Agreement') }}
                        </flux:button>
                    @endcan
                </div>

                @if ($showAgreementForm)
                    <form wire:submit="createAgreement" class="mb-4 grid gap-3 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800 sm:grid-cols-2">
                        <flux:input wire:model="start_date" :label="__('Start Date')" type="date" required />
                        <flux:input wire:model="duration_months" :label="__('Duration (months)')" type="number" min="1" required />
                        <flux:input wire:model="monthly_rent" :label="__('Monthly Rent (KES)')" type="number" step="0.01" min="0" required />
                        <flux:select wire:model="agreement_house_id" :label="__('House')">
                            <option value="">{{ __('None') }}</option>
                            @foreach (\App\Models\House::orderBy('house_number')->get() as $house)
                                <option value="{{ $house->id }}">{{ $house->house_number }} — {{ $house->location }}</option>
                            @endforeach
                        </flux:select>

                        <div class="sm:col-span-2">
                            <flux:button variant="primary" size="sm" type="submit">{{ __('Create Agreement') }}</flux:button>
                        </div>
                    </form>
                @endif

                @if ($tenant->agreements->isEmpty())
                    <flux:text class="text-zinc-500">{{ __('No agreements yet.') }}</flux:text>
                @else
                    <flux:table :striped="true">
                        <flux:table.columns>
                            <flux:table.column>House</flux:table.column>
                            <flux:table.column>Period</flux:table.column>
                            <flux:table.column>Rent</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column class="text-end">Actions</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($tenant->agreements as $agreement)
                                <flux:table.row :key="$agreement->id">
                                    <flux:table.cell>{{ $agreement->house?->house_number ?? '—' }}</flux:table.cell>
                                    <flux:table.cell>
                                        {{ format_date($agreement->start_date) }} – {{ format_date($agreement->end_date) }}
                                    </flux:table.cell>
                                    <flux:table.cell>{{ format_currency($agreement->monthly_rent) }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge size="sm" :color="$agreement->status->value === 'active' ? 'green' : 'zinc'">
                                            {{ $agreement->status->label() }}
                                        </flux:badge>
                                    </flux:table.cell>
                                    <flux:table.cell class="text-end">
                                        @if ($agreement->status->value === 'active')
                                            @can('terminate', $agreement)
                                                <flux:button size="sm" variant="danger"
                                                    wire:click="terminateAgreement({{ $agreement->id }})"
                                                    wire:confirm="Terminate this agreement?">
                                                    {{ __('Terminate') }}
                                                </flux:button>
                                            @endcan
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading level="3" class="mb-3">{{ __('Payment History') }}</flux:heading>

                @if ($tenant->payments->isEmpty())
                    <flux:text class="text-zinc-500">{{ __('No payments recorded.') }}</flux:text>
                @else
                    <flux:table :striped="true">
                        <flux:table.columns>
                            <flux:table.column>Receipt</flux:table.column>
                            <flux:table.column>Date</flux:table.column>
                            <flux:table.column>Amount</flux:table.column>
                            <flux:table.column class="text-end">Actions</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach ($tenant->payments as $payment)
                                <flux:table.row :key="$payment->id">
                                    <flux:table.cell>{{ $payment->receipt_number }}</flux:table.cell>
                                    <flux:table.cell>{{ format_date($payment->payment_date) }}</flux:table.cell>
                                    <flux:table.cell class="font-semibold">{{ format_currency($payment->amount_paid) }}</flux:table.cell>
                                    <flux:table.cell class="text-end">
                                        <flux:button size="sm" :href="route('payments.show', $payment)" wire:navigate>
                                            {{ __('View') }}
                                        </flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @endif
            </div>
        </div>
    </div>
</div>
