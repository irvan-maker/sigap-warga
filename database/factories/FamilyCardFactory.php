<?php

namespace Database\Factories;

use App\Models\FamilyCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FamilyCard> */
class FamilyCardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'family_number' => fake()->unique()->numerify('################'),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
