<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dob = $this->faker->date(max: Carbon::now()->subYears(50));
        return [
            //
            'email' => $this->faker->unique()->userName() . '@' . $this->faker->randomElement(config('auth.allowed_domains')),
            'full_name' => $this->faker->name(),
            'name' => $this->faker->userName(),
            'dob' => $dob,
            'years_of_service' => $this->faker->dateTimeBetween()->format('Y-m-d'),
        ];
    }
}
