<?php

namespace App\Livewire\Houses;

use App\Enums\HouseStatus;
use App\Models\House;
use App\Services\HouseOccupancyService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Edit House')]
#[Layout('layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public House $house;

    public string $house_number = '';

    public string $location = '';

    public string $rent_amount = '';

    public string $status = 'available';

    public string $description = '';

    public $image;

    public bool $removeImage = false;

    public function mount(House $house): void
    {
        $this->authorize('update', $house);

        $this->house = $house;
        $this->house_number = $house->house_number;
        $this->location = $house->location;
        $this->rent_amount = (string) $house->rent_amount;
        $this->status = $house->status === HouseStatus::Occupied ? HouseStatus::Occupied->value : $house->status->value;
        $this->description = (string) $house->description;
    }

    protected function rules(): array
    {
        return [
            'house_number' => ['required', 'string', 'max:50', 'unique:houses,house_number,'.$this->house->id],
            'location' => ['required', 'string', 'max:255'],
            'rent_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', 'in:available,occupied,maintenance'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function update(HouseOccupancyService $occupancy): void
    {
        $this->authorize('update', $this->house);

        $validated = $this->validate();

        $newImage = null;

        if ($this->image) {
            if ($this->house->image) {
                Storage::disk('public')->delete($this->house->image);
            }
            $newImage = $this->image->store('houses', 'public');
        } elseif ($this->removeImage && $this->house->image) {
            Storage::disk('public')->delete($this->house->image);
        }

        $this->house->update([
            'house_number' => $validated['house_number'],
            'location' => $validated['location'],
            'rent_amount' => $validated['rent_amount'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?: null,
            'image' => $newImage ?? ($this->removeImage ? null : $this->house->image),
        ]);

        $occupancy->refresh($this->house);

        Flux::toast(variant: 'success', text: __('House updated.'));

        $this->redirectRoute('houses.index');
    }

    public function delete(HouseOccupancyService $occupancy): void
    {
        $this->authorize('delete', $this->house);

        $occupancy->unassign($this->house);

        if ($this->house->image) {
            Storage::disk('public')->delete($this->house->image);
        }

        $this->house->delete();

        Flux::toast(variant: 'success', text: __('House deleted.'));

        $this->redirectRoute('houses.index');
    }

    public function render()
    {
        return view('livewire.houses.edit');
    }
}
