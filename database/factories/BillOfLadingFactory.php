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
        $portOfLoading = fake()->city().' Port';
        $portOfDischarge = fake()->city().' Port';
        $goods = fake()->paragraph();
        $packageCount = fake()->numberBetween(10, 500).' cartons';
        $measurement = fake()->randomFloat(2, 1, 80);

        return [
            'bl_number' => 'BL-'.fake()->unique()->numerify('######'),
            'customer_id' => User::factory()->customer(),
            'shipment_type' => BillOfLading::TYPE_IMPORT,
            'shipping_method' => BillOfLading::SHIPPING_METHOD_FCL,
            'carrier_name' => fake()->optional()->company(),
            'shipment_description' => fake()->sentence(6),
            'port_of_loading' => $portOfLoading,
            'port_of_discharge' => $portOfDischarge,
            'goods_description' => $goods,
            'package_count' => $packageCount,
            'gross_weight_kg' => fake()->randomFloat(2, 100, 25000),
            'measurement_cbm' => $measurement,
            'input_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status' => BillOfLading::STATUS_IN_PROGRESS,
            'phase' => 'Input',
            'gps_tracking_url' => fake()->optional()->url(),
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
