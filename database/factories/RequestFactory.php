<?php

namespace Database\Factories;

use App\Models\EventCost;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
//            'employee_requester' => $this->faker->numberBetween(0, 1000),
            'employee_requester' => $this->faker->unique()->userName() . '@' . $this->faker->randomElement(config('auth.allowed_domains')),
            'employee_recipient' => Member::inrandomOrder()->first() ?? Member::factory(10)->create(),
            'event_cost_id' => EventCost::inrandomOrder()->first() ?? EventCost::factory(10)->create(),
            'iban' => $this->faker->iban(countryCode: 'NL', length: 18),
            'account_name' => $this->faker->name(),
            'paid_at' => null
        ];
    }
}
