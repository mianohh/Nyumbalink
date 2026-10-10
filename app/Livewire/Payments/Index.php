<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Payments')]
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $date_from = '';

    public string $date_to = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $payments = Payment::query()
            ->with(['tenant.house'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('receipt_number', 'like', "%{$this->search}%")
                        ->orWhereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->date_from !== '', fn ($query) => $query->whereDate('payment_date', '>=', $this->date_from))
            ->when($this->date_to !== '', fn ($query) => $query->whereDate('payment_date', '<=', $this->date_to))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(15);

        $total = (float) Payment::query()
            ->when($this->date_from !== '', fn ($query) => $query->whereDate('payment_date', '>=', $this->date_from))
            ->when($this->date_to !== '', fn ($query) => $query->whereDate('payment_date', '<=', $this->date_to))
            ->sum('amount_paid');

        return view('livewire.payments.index', [
            'payments' => $payments,
            'total' => $total,
        ]);
    }
}
