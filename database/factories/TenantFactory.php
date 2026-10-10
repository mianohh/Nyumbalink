<?php

namespace Database\Factories;

use App\Models\House;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'contact' => '+2547'.fake()->numerify('########'),
            'id_number' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'house_id' => null,
        ];
    }

    public function inHouse(House $house): static
    {
        return $this->state(fn () => ['house_id' => $house->id]);
    }
}
