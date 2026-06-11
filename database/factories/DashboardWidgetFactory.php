<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DashboardWidget>
 */
class DashboardWidgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'widget_type' => fake()->randomElement([
                'stats',
                'chart_weekly',
                'chart_monthly',
                'chart_yearly',
                'heatmap',
                'gantt',
                'recent_todos',
                'category_summary',
                'priority_summary'
            ]),
            'position' => fake()->numberBetween(1, 10),
            'size' => fake()->randomElement(['small', 'medium', 'large', 'full']),
            'is_visible' => true,
            'settings' => [
                'color' => fake()->safeColorName(),
                'limit' => fake()->numberBetween(5, 20),
            ],
        ];
    }

    /**
     * Indicate that the widget is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
