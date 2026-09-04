<?php

namespace Database\Factories;

use App\Models\BillOfLading;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Log>
 */
class LogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loggable_type' => BillOfLading::class,
            'loggable_id' => BillOfLading::factory(),
            'user_id' => User::factory()->admin(),
            'event' => Log::EVENT_UPDATED,
            'description' => 'Updated BillOfLading: status',
            'changes' => [
                'status' => [
                    'old' => 'Pending',
                    'new' => 'In Progress',
                ],
            ],
        ];
    }
}
