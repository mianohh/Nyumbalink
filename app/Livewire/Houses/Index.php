<?php

namespace App\Livewire\Houses;

use App\Enums\HouseStatus;
use App\Models\House;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Houses')]
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $houses = House::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('house_number', 'like', "%{$this->search}%")
                        ->orWhere('location', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== '', function ($query) {
                $query->where('status', $this->status);
            })
            ->withCount('tenants')
            ->orderBy('house_number')
            ->paginate(12);

        return view('livewire.houses.index', [
            'houses' => $houses,
            'statuses' => HouseStatus::cases(),
        ]);
    }
}
