<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_code' => 'LOC'.fake()->unique()->numberBetween(100, 99999),
            'location_name' => 'Parkir '.fake()->company(),
            'address' => fake()->address(),
            // A fixed point keeps distance assertions deterministic.
            'latitude' => -6.1944000,
            'longitude' => 106.8229000,
            'radius_meter' => 10,
            'gps_accuracy_limit' => 20,
            'status' => Location::ACTIVE,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Location::INACTIVE]);
    }
}
