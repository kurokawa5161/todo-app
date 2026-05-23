<?php

namespace Database\Factories;

use App\Models\ExportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExportTemplate>
 */
class ExportTemplateFactory extends Factory
{
    protected $model = ExportTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Weekly Report', 'Monthly Summary', 'Project Export', 'Task List']),
            'description' => fake()->sentence(),
            'format' => fake()->randomElement(['csv', 'excel', 'json', 'xml']),
            'fields' => ['id', 'title', 'status', 'end_date'],
            'order' => ['id'],
            'filters' => [],
        ];
    }
}
