<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Record Payment')]
#[Layout('layouts.app')]
class Create extends Component
{
    public ?int $tenant_id = null;

    public string $amount_paid = '';

    public string $payment_date = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
        $this->tenant_id = (int) request()->query('tenant_id', 0) ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'amount_paid' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $this->authorize('create', Payment::class);

        $validated = $this->validate();

        $payment = Payment::record($validated);

        Flux::toast(variant: 'success', text: __('Payment recorded. Receipt '.$payment->receipt_number));

        $this->redirectRoute('payments.show', $payment);
    }

    public function render(): View
    {
        $tenant = $this->tenant_id ? Tenant::with('house')->find($this->tenant_id) : null;

        return view('livewire.payments.create', [
            'tenants' => Tenant::with('house')->orderBy('name')->get(),
            'preview' => $tenant ? [
                'house' => $tenant->house?->house_number,
                'rent' => $tenant->house?->rent_amount,
                'paid' => $tenant->totalPaidForMonth(),
                'balance' => $tenant->balanceForMonth(),
            ] : null,
        ]);
    }
}
