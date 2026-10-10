<?php

namespace Database\Factories;

use App\Enums\HouseStatus;
use App\Models\House;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<House>
 */
class HouseFactory extends Factory
{
    protected $model = House::class;

    public function definition(): array
    {
        return [
            'house_number' => 'H-'.fake()->unique()->numberBetween(1000, 9999),
            'location' => fake()->randomElement(['Kilimani', 'Westlands', 'Kasarani', 'Langata', 'Embakasi', 'Thika Road']),
            'rent_amount' => fake()->randomElement([8000, 12000, 15000, 20000, 25000, 35000]),
            'status' => HouseStatus::Available,
            'description' => fake()->optional()->sentence(8),
            'image' => null,
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn () => ['status' => HouseStatus::Occupied]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => ['status' => HouseStatus::Maintenance]);
    }
}
