<?php

namespace Database\Factories;

use App\Models\Report;
use App\Services\TicketNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => fn (): string => app(TicketNumberGenerator::class)->generate(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'reported_at' => now(),
        ];
    }
}
