<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categories = Category::factory(10)->create([
            'user_id' => $user->id
        ]);

        $tags = Tag::factory(10)->create([
            'user_id' => $user->id
        ]);

        $todos = Todo::factory(10)->create([
            'user_id' => $user->id,
            'category_id' => $categories->random()->id

        ]);


        foreach ($todos as $todo) {
            Comment::factory(rand(0, 3))->create([
                'user_id' => $user->id,
                'todo_id' => $todo->id,
            ]);

            $todo->tags()->attach($tags->random(rand(0, 3))->pluck('id'));
        }
    }
}
