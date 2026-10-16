<?php

namespace Database\Factories;

use App\Enums\AgreementStatus;
use App\Models\House;
use App\Models\RentalAgreement;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalAgreement>
 */
class RentalAgreementFactory extends Factory
{
    protected $model = RentalAgreement::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', '-1 week');
        $months = fake()->numberBetween(6, 24);
        $end = (clone $start)->modify("+{$months} months");

        return [
            'tenant_id' => Tenant::factory(),
            'house_id' => House::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'duration_months' => $months,
            'monthly_rent' => fake()->randomElement([8000, 12000, 15000, 20000, 25000]),
            'status' => AgreementStatus::Active,
        ];
    }
}
