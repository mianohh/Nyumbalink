<?php

namespace App\Livewire\Houses;

use App\Models\House;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Add House')]
#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $house_number = '';

    public string $location = '';

    public string $rent_amount = '';

    public string $status = 'available';

    public string $description = '';

    public ?TemporaryUploadedFile $image = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'house_number' => ['required', 'string', 'max:50', 'unique:houses,house_number'],
            'location' => ['required', 'string', 'max:255'],
            'rent_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', 'in:available,maintenance'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function save(): void
    {
        $this->authorize('create', House::class);

        $validated = $this->validate();

        $validated['image'] = $this->image
            ? $this->image->store('houses', 'public')
            : null;

        House::create($validated);

        Flux::toast(variant: 'success', text: __('House added successfully.'));

        $this->redirectRoute('houses.index');
    }

    public function render(): View
    {
        return view('livewire.houses.create');
    }
}
