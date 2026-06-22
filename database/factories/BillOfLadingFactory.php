<?php

namespace Database\Factories;

use App\Models\BillOfLading;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillOfLading>
 */
class BillOfLadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $origin = fake()->city();
        $destination = fake()->city();

        return [
            'bl_number' => 'BL-'.fake()->unique()->numerify('######'),
            'customer_id' => User::factory()->customer(),
            'shipment_description' => fake()->sentence(6),
            'origin' => $origin.' Port',
            'destination' => $destination.' Port',
            'items_description' => fake()->paragraph(),
            'quantity' => fake()->numberBetween(10, 500).' cartons',
            'gross_weight_kg' => fake()->randomFloat(2, 100, 25000),
            'volume_cbm' => fake()->randomFloat(2, 1, 80),
            'input_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(BillOfLading::STATUSES),
            'phase' => fake()->randomElement(BillOfLading::PHASES),
            'gps_tracking_url' => fake()->optional()->url(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
