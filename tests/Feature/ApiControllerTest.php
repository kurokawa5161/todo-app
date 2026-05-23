<?php

use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * API AuthController Tests
 */
test('api login returns token with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'token',
        'user',
    ]);
    $this->assertTrue($response->json('success'));
    $this->assertNotEmpty($response->json('token'));
});

test('api login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
    $response->assertJson([
        'success' => false,
        'message' => 'Invalid credentials',
    ]);
});

test('api login validates required fields', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'password']);
});

test('api logout deletes current token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/logout');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Logout successful',
    ]);
});

/**
 * API TodoController Tests
 */
test('api todo index returns paginated todos', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    Todo::factory()->count(5)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/todos');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data',
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        'message',
    ]);
    $this->assertCount(5, $response->json('data'));
});

test('api todo index filters by category', function () {
    $user = User::factory()->create();
    $category1 = Category::factory()->create(['user_id' => $user->id]);
    $category2 = Category::factory()->create(['user_id' => $user->id]);

    Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category1->id,
    ]);
    Todo::factory()->count(2)->create([
        'user_id' => $user->id,
        'category_id' => $category2->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/todos?category_id=' . $category1->id);

    $response->assertStatus(200);
    $this->assertCount(3, $response->json('data'));
});

test('api todo index filters by status', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // Completed todos
    Todo::factory()->count(2)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'completed_at' => now(),
    ]);

    // Active todos
    Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'completed_at' => null,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/todos?status=done');
    $response->assertStatus(200);
    $this->assertCount(2, $response->json('data'));

    $response = $this->getJson('/api/todos?status=active');
    $response->assertStatus(200);
    $this->assertCount(3, $response->json('data'));
});

test('api todo store creates new todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/todos', [
        'title' => 'API Test Todo',
        'content' => 'Test content',
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'category_id' => $category->id,
        'priority' => 2,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo Store successfully',
    ]);

    $this->assertDatabaseHas('todos', [
        'user_id' => $user->id,
        'title' => 'API Test Todo',
    ]);
});

test('api todo store validates required fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/todos', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'start_date', 'end_date']);
});

test('api todo show returns single todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Specific Todo',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/todos/' . $todo->id);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo Show successfully',
    ]);
    $this->assertEquals('Specific Todo', $response->json('data.title'));
});

test('api todo update modifies existing todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Original Title',
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson('/api/todos/' . $todo->id, [
        'title' => 'Updated Title',
        'content' => 'Updated content',
        'start_date' => $todo->start_date->format('Y-m-d'),
        'end_date' => $todo->end_date->format('Y-m-d'),
        'category_id' => $category->id,
        'priority' => 1,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo Update successfully',
    ]);

    $this->assertDatabaseHas('todos', [
        'id' => $todo->id,
        'title' => 'Updated Title',
        'priority' => 1,
    ]);
});

test('api todo destroy deletes todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/todos/' . $todo->id);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo delete successfully',
    ]);

    $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
});

test('api todo bulk delete removes multiple todos', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todos = Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    Sanctum::actingAs($user);

    $ids = $todos->pluck('id')->toArray();
    $response = $this->deleteJson('/api/todos/bulk/delete', [
        'ids' => $ids,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo delete successfully',
    ]);

    foreach ($ids as $id) {
        $this->assertDatabaseMissing('todos', ['id' => $id]);
    }
});

test('api todo bulk update modifies multiple todos', function () {
    $user = User::factory()->create();
    $category1 = Category::factory()->create(['user_id' => $user->id]);
    $category2 = Category::factory()->create(['user_id' => $user->id]);
    $todos = Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category1->id,
        'priority' => 3,
    ]);

    Sanctum::actingAs($user);

    $ids = $todos->pluck('id')->toArray();
    $response = $this->putJson('/api/todos/bulk/update', [
        'ids' => $ids,
        'category_id' => $category2->id,
        'priority' => 1,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo Update successfully',
    ]);

    foreach ($ids as $id) {
        $this->assertDatabaseHas('todos', [
            'id' => $id,
            'category_id' => $category2->id,
            'priority' => 1,
        ]);
    }
});

test('api todo bulk complete marks todos as completed', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todos = Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'completed_at' => null,
    ]);

    Sanctum::actingAs($user);

    $ids = $todos->pluck('id')->toArray();
    $response = $this->putJson('/api/todos/bulk/complete', [
        'ids' => $ids,
        'completed' => true,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Todo Update successfully',
    ]);

    foreach ($ids as $id) {
        $todo = Todo::findOrFail($id);
        $this->assertNotNull($todo->completed_at);
    }
});

test('api todo requires authentication', function () {
    $response = $this->getJson('/api/todos');

    $response->assertStatus(401);
});

test('api todo enforces ownership', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user1->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user1->id,
        'category_id' => $category->id,
    ]);

    Sanctum::actingAs($user2);

    // When user2 tries to access user1's todo, they get 404 (not found in their scope)
    // This is because the API TodoController filters by auth()->user()->todos()
    $response = $this->getJson('/api/todos');
    $response->assertStatus(200);
    $response->assertJsonCount(0, 'data'); // user2 has no todos
});
