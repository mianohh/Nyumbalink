<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Payment Details')]
#[Layout('layouts.app')]
class Show extends Component
{
    public Payment $payment;

    public function mount(Payment $payment): void
    {
        $this->authorize('view', $payment);

        $this->payment = $payment;
    }

    public function render()
    {
        $this->payment->load(['tenant.house']);

        return view('livewire.payments.show');
    }
}
