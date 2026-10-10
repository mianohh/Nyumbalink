<?php

namespace App\Livewire;

use App\Models\House;
use App\Models\Payment;
use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $monthRevenue = (float) Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount_paid');

        $totalRevenue = (float) Payment::sum('amount_paid');

        $houseStatuses = House::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonthsNoOverflow($i));

        $monthlyTotals = $months->mapWithKeys(function ($month) {
            return [
                $month->format('M Y') => (float) Payment::whereMonth('payment_date', $month->month)
                    ->whereYear('payment_date', $month->year)
                    ->sum('amount_paid'),
            ];
        });

        return view('livewire.dashboard', [
            'totalTenants' => Tenant::count(),
            'availableHouses' => (int) ($houseStatuses['available'] ?? 0),
            'occupiedHouses' => (int) ($houseStatuses['occupied'] ?? 0),
            'maintenanceHouses' => (int) ($houseStatuses['maintenance'] ?? 0),
            'monthRevenue' => $monthRevenue,
            'totalRevenue' => $totalRevenue,
            'recentPayments' => Payment::with(['tenant', 'tenant.house'])->latest('id')->take(5)->get(),
            'revenueLabels' => $monthlyTotals->keys()->values(),
            'revenueValues' => $monthlyTotals->values(),
            'statusLabels' => [
                __('Available'),
                __('Occupied'),
                __('Maintenance'),
            ],
            'statusValues' => [
                (int) ($houseStatuses['available'] ?? 0),
                (int) ($houseStatuses['occupied'] ?? 0),
                (int) ($houseStatuses['maintenance'] ?? 0),
            ],
        ]);
    }
}
