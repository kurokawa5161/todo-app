<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'content' => fake()->sentence(),
            'start_date' => $start_date = fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->dateTimeBetween($start_date, '+2 month'),
            'completed_at' => null,
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'priority' => fake()->numberBetween(1, 3),
            'parent_id' => null,
            'is_pinned' => fake()->boolean(),
            'image_path' => fake()->optional()->filePath(),
        ];
    }
}
