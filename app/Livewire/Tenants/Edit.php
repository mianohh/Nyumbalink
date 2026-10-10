<?php

namespace App\Livewire\Tenants;

use App\Models\House;
use App\Models\Tenant;
use App\Services\HouseOccupancyService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Tenant')]
#[Layout('layouts.app')]
class Edit extends Component
{
    public Tenant $tenant;

    public string $name = '';

    public string $contact = '';

    public string $id_number = '';

    public ?int $house_id = null;

    public function mount(Tenant $tenant): void
    {
        $this->authorize('update', $tenant);

        $this->tenant = $tenant;
        $this->name = $tenant->name;
        $this->contact = $tenant->contact;
        $this->id_number = $tenant->id_number;
        $this->house_id = $tenant->house_id;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:30'],
            'id_number' => ['required', 'string', 'max:30', 'unique:tenants,id_number,'.$this->tenant->id],
            'house_id' => ['nullable', 'exists:houses,id'],
        ];
    }

    public function update(HouseOccupancyService $occupancy): void
    {
        $this->authorize('update', $this->tenant);

        $validated = $this->validate();

        $previousHouseId = $this->tenant->house_id;

        $this->tenant->update($validated);

        if ($previousHouseId !== $this->house_id) {
            if ($previousHouseId) {
                $occupancy->refresh(House::find($previousHouseId));
            }

            if ($this->house_id) {
                $occupancy->assign(House::findOrFail($this->house_id), $this->tenant);
            } else {
                $occupancy->refresh($this->tenant->house);
            }
        }

        Flux::toast(variant: 'success', text: __('Tenant updated.'));

        $this->redirectRoute('tenants.index');
    }

    public function delete(HouseOccupancyService $occupancy): void
    {
        $this->authorize('delete', $this->tenant);

        if ($this->tenant->payments()->exists()) {
            Flux::toast(variant: 'error', text: __('Cannot delete a tenant with recorded payments.'));

            return;
        }

        $house = $this->tenant->house;

        $this->tenant->agreements()->delete();
        $this->tenant->delete();

        if ($house) {
            $occupancy->refresh($house);
        }

        Flux::toast(variant: 'success', text: __('Tenant deleted.'));

        $this->redirectRoute('tenants.index');
    }

    public function render()
    {
        return view('livewire.tenants.edit', [
            'houses' => House::orderBy('house_number')->get(),
        ]);
    }
}
