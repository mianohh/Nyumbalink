<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'contact',
        'id_number',
        'house_id',
    ];

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(RentalAgreement::class);
    }

    /**
     * Total paid by this tenant in the given month (default: current month).
     */
    public function totalPaidForMonth(?int $month = null, ?int $year = null): float
    {
        $month ??= (int) now()->format('n');
        $year ??= (int) now()->format('Y');

        return (float) $this->payments()
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->sum('amount_paid');
    }

    /**
     * Balance owed for the given month: monthly rent (from active agreement)
     * minus total paid that month.
     */
    public function balanceForMonth(?int $month = null, ?int $year = null): float
    {
        $month ??= (int) now()->format('n');
        $year ??= (int) now()->format('Y');

        $rent = (float) ($this->agreements()
            ->where('status', 'active')
            ->latest('start_date')
            ->value('monthly_rent') ?? $this->house?->rent_amount ?? 0);

        return $rent - $this->totalPaidForMonth($month, $year);
    }
}
