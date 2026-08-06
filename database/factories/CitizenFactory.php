<?php

namespace Database\Factories;

use App\Models\Citizen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citizen>
 */
class CitizenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '628'.fake()->unique()->numerify('##########');

        return [
            'name' => fake()->name(),
            'phone' => $phone,
            'phone_normalized' => $phone,
            'is_active' => true,
        ];
    }
}
