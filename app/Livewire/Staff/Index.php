<?php

namespace App\Livewire\Staff;

use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Staff')]
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(User $user): void
    {
        $this->authorize('delete', $user);

        if ($user->is(auth()->user())) {
            Flux::toast(variant: 'error', text: __('You cannot delete your own account.'));

            return;
        }

        $user->delete();

        Flux::toast(variant: 'success', text: __('Staff member removed.'));
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.staff.index', compact('users'));
    }
}
