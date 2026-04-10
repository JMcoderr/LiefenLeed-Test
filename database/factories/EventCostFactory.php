<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventCost>
 */
class EventCostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'event_id' => Event::inRandomOrder()->first() ?? Event::factory()->create(),
            'amount' => $this->faker->randomFloat(2, 5, 20),
            'start_date' => now(),
            'end_date' => null
        ];
    }
}
