<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teamNames = [
            'Development Team',
            'Design Team',
            'Marketing Team',
            'Sales Team',
            'Product Team',
            'Engineering Team',
            'QA Team',
            'Support Team',
        ];

        return [
            'name' => fake()->randomElement($teamNames) . ' ' . fake()->numberBetween(1, 10),
        ];
    }
}
