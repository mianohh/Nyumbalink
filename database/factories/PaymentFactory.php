<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'amount_paid' => fake()->randomElement([3000, 5000, 8000, 10000, 12000, 15000]),
            'payment_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'receipt_number' => Payment::generateReceiptNumber(),
            'notes' => fake()->optional()->sentence(6),
        ];
    }
}
