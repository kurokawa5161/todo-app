<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedSearch>
 */
class SavedSearchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->words(3, true),
            'conditions' => [
                'filter' => fake()->randomElement(['all', 'active', 'completed']),
                'q' => fake()->optional()->word(),
                'priority' => fake()->optional()->randomElement(['high', 'medium', 'low']),
            ],
        ];
    }
}
