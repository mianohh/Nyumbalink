<?php

namespace App\Livewire\Houses;

use App\Models\House;
use App\Models\Tenant;
use App\Services\HouseOccupancyService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('House Details')]
#[Layout('layouts.app')]
class Show extends Component
{
    public House $house;

    public ?int $tenantToAssign = null;

    public function mount(House $house): void
    {
        $this->authorize('view', $house);

        $this->house = $house;
    }

    public function assign(HouseOccupancyService $occupancy): void
    {
        $this->authorize('update', $this->house);

        $this->validate(['tenantToAssign' => ['required', 'exists:tenants,id']]);

        $tenant = Tenant::findOrFail($this->tenantToAssign);

        $occupancy->assign($this->house, $tenant);

        $this->house->refresh();

        $this->tenantToAssign = null;

        Flux::toast(variant: 'success', text: __('Tenant assigned to house.'));
    }

    public function unassign(HouseOccupancyService $occupancy): void
    {
        $this->authorize('update', $this->house);

        $occupancy->unassign($this->house);

        $this->house->refresh();

        Flux::toast(variant: 'success', text: __('Tenant unassigned.'));
    }

    public function render()
    {
        $this->house->load(['tenants', 'agreements' => fn ($q) => $q->latest('start_date')]);

        $availableTenants = Tenant::whereNull('house_id')->orderBy('name')->get();

        return view('livewire.houses.show', [
            'availableTenants' => $availableTenants,
        ]);
    }
}
