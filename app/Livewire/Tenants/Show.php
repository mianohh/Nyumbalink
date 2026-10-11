<?php

namespace App\Livewire\Tenants;

use App\Enums\AgreementStatus;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tenant Details')]
#[Layout('layouts.app')]
class Show extends Component
{
    public Tenant $tenant;

    public bool $showAgreementForm = false;

    public string $start_date = '';

    public int $duration_months = 12;

    public string $monthly_rent = '';

    public ?int $agreement_house_id = null;

    public function mount(Tenant $tenant): void
    {
        $this->authorize('view', $tenant);

        $this->tenant = $tenant;
        $this->start_date = now()->format('Y-m-d');
        $this->monthly_rent = (string) ($tenant->house->rent_amount ?? '');
        $this->agreement_house_id = $tenant->house_id;
    }

    /**
     * @return array<string, mixed>
     */
    protected function agreementRules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'agreement_house_id' => ['nullable', 'exists:houses,id'],
        ];
    }

    public function createAgreement(): void
    {
        $this->authorize('create', RentalAgreement::class);

        $validated = $this->validate($this->agreementRules());

        $start = Carbon::parse($validated['start_date']);
        $end = (clone $start)->addMonths($validated['duration_months']);

        RentalAgreement::create([
            'tenant_id' => $this->tenant->id,
            'house_id' => $validated['agreement_house_id'],
            'start_date' => $start,
            'end_date' => $end,
            'duration_months' => $validated['duration_months'],
            'monthly_rent' => $validated['monthly_rent'],
            'status' => AgreementStatus::Active,
        ]);

        $this->showAgreementForm = false;

        Flux::toast(variant: 'success', text: __('Rental agreement created.'));
    }

    public function terminateAgreement(RentalAgreement $agreement): void
    {
        $this->authorize('terminate', $agreement);

        abort_unless($agreement->tenant_id === $this->tenant->id, 404);

        $agreement->update(['status' => AgreementStatus::Terminated]);

        Flux::toast(variant: 'success', text: __('Agreement terminated.'));
    }

    public function render(): View
    {
        $this->tenant->load([
            'house',
            'payments' => fn ($q) => $q->latest('payment_date')->latest('id'),
            'agreements' => fn ($q) => $q->latest('start_date'),
        ]);

        return view('livewire.tenants.show');
    }
}
