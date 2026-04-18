<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            '仕事',
            '個人',
            '勉強',
            '買い物',
            '趣味',
            '健康',
            '家事',
            'プロジェクト',
            '読書',
            '運動',
        ];

        return [
            'name' => fake()->randomElement($categories),
            'user_id' => User::factory(),
            'color' => fake()->randomElement(['red', 'yellow', 'green', 'blue', 'purple', 'pink', 'gray']),
        ];
    }
}
