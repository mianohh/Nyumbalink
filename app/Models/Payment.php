<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'amount_paid',
        'payment_date',
        'receipt_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Generate a unique receipt number: RCP-YYYYmmdd-XXX
     */
    public static function generateReceiptNumber(): string
    {
        do {
            $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 3));
            $receiptNumber = 'RCP-'.now()->format('Ymd').'-'.$suffix;
        } while (self::where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }

    /**
     * Create a payment with a freshly generated receipt number inside a transaction.
     */
    public static function record(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $attributes['receipt_number'] = self::generateReceiptNumber();

            return self::create($attributes);
        });
    }
}
