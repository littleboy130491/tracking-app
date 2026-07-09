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
        $items = fake()->paragraph();
        $quantity = fake()->numberBetween(10, 500).' cartons';
        $volume = fake()->randomFloat(2, 1, 80);

        return [
            'bl_number' => 'BL-'.fake()->unique()->numerify('######'),
            'booking_number' => fake()->optional()->bothify('BK########'),
            'customer_id' => User::factory()->customer(),
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'carrier_name' => fake()->optional()->company(),
            'shipment_description' => fake()->sentence(6),
            'origin' => $origin.' Port',
            'destination' => $destination.' Port',
            'port_of_loading' => $origin.' Port',
            'port_of_discharge' => $destination.' Port',
            'items_description' => $items,
            'goods_description' => $items,
            'quantity' => $quantity,
            'package_count' => $quantity,
            'gross_weight_kg' => fake()->randomFloat(2, 100, 25000),
            'volume_cbm' => $volume,
            'measurement_cbm' => $volume,
            'input_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(BillOfLading::STATUSES),
            'phase' => fake()->randomElement(BillOfLading::PHASES),
            'gps_tracking_url' => fake()->optional()->url(),
            'note' => fake()->optional()->sentence(),
            'customer_note' => fake()->optional()->sentence(),
        ];
    }

    public function export(): static
    {
        return $this->state(fn (): array => [
            'shipment_type' => BillOfLading::TYPE_EXPORT,
        ]);
    }
}
