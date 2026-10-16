<?php

namespace App\Livewire\Staff;

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Add Staff Member')]
#[Layout('layouts.app')]
class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'staff';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,staff'],
        ];
    }

    public function save(): void
    {
        $this->authorize('create', User::class);

        $validated = $this->validate();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::from($validated['role']),
        ]);

        Flux::toast(variant: 'success', text: __('Staff member created.'));

        $this->redirectRoute('staff.index');
    }

    public function render(): View
    {
        return view('livewire.staff.create');
    }
}
