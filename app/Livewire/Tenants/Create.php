<?php

namespace App\Livewire\Tenants;

use App\Models\House;
use App\Models\Tenant;
use App\Services\HouseOccupancyService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add Tenant')]
#[Layout('layouts.app')]
class Create extends Component
{
    public string $name = '';

    public string $contact = '';

    public string $id_number = '';

    public ?int $house_id = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:30'],
            'id_number' => ['required', 'string', 'max:30', 'unique:tenants,id_number'],
            'house_id' => ['nullable', 'exists:houses,id'],
        ];
    }

    public function save(HouseOccupancyService $occupancy): void
    {
        $this->authorize('create', Tenant::class);

        $validated = $this->validate();

        $tenant = Tenant::create($validated);

        if ($this->house_id) {
            $occupancy->assign(House::findOrFail($this->house_id), $tenant);
        }

        Flux::toast(variant: 'success', text: __('Tenant added successfully.'));

        $this->redirectRoute('tenants.index');
    }

    public function render()
    {
        return view('livewire.tenants.create', [
            'houses' => House::orderBy('house_number')->get(),
        ]);
    }
}
