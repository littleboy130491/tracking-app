<?php

namespace Database\Factories;

use App\Models\BillOfLading;
use App\Models\Container;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Container>
 */
class ContainerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_of_lading_id' => BillOfLading::factory(),
            'container_number' => strtoupper(fake()->unique()->bothify('????#######')),
            'seal_number' => strtoupper(fake()->bothify('SEAL#####')),
            'container_type' => fake()->randomElement(["20'GP", "40'HC", '40HQ']),
            'package_count' => fake()->numberBetween(10, 1200).' BAGS',
            'gross_weight_kg' => fake()->randomFloat(3, 1000, 28000),
            'measurement_cbm' => fake()->randomFloat(4, 10, 70),
            'sort_order' => 1,
        ];
    }
}
