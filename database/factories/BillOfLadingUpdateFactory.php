<?php

namespace Database\Factories;

use App\Models\BillOfLading;
use App\Models\BillOfLadingUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillOfLadingUpdate>
 */
class BillOfLadingUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_of_lading_id' => BillOfLading::factory(),
            'user_id' => User::factory()->admin(),
            'status' => fake()->randomElement(BillOfLading::STATUSES),
            'phase' => fake()->randomElement(BillOfLading::PHASES),
            'note' => fake()->sentence(),
        ];
    }
}
