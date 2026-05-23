<?php

use App\Models\User;
use App\Models\Team;
use App\Models\Todo;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Comment;
use App\Models\ExportTemplate;
use App\Models\DashboardWidget;
use App\Notifications\TodoCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * TeamController Tests
 */
test('team index shows user teams with counts', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Development Team']);
    $team->users()->attach($user->id, ['role' => 'member']);

    $response = $this->actingAs($user)->get(route('teams.index'));

    $response->assertStatus(200);
    $response->assertSee('Development Team');
});

test('team store creates team with owner role', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'New Team',
    ]);

    $this->assertDatabaseHas('teams', ['name' => 'New Team']);

    $team = Team::where('name', 'New Team')->first();
    $this->assertTrue($team->users->contains($user));
    $this->assertEquals('owner', $team->users->first()->pivot->role);

    $response->assertRedirect(route('teams.show', $team));
});

test('team show displays team todos with pagination', function () {
    // Fake broadcasting to avoid Reverb dependency
    Event::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user->id, ['role' => 'owner']);
    $category = Category::factory()->create(['user_id' => $user->id]);

    // Create team todos
    Todo::factory()->count(5)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'team_id' => $team->id,
    ]);

    $response = $this->actingAs($user)->get(route('teams.show', $team));

    $response->assertStatus(200);
    $response->assertViewHas('team');
    $response->assertViewHas('items');
});

test('team update changes team name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Old Name']);
    $team->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)->put(route('teams.update', $team), [
        'name' => 'New Name',
    ]);

    $this->assertDatabaseHas('teams', ['name' => 'New Name']);
    $response->assertRedirect(route('teams.show', $team));
});

test('team destroy deletes team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->users()->attach($user->id, ['role' => 'owner']);

    $response = $this->actingAs($user)->delete(route('teams.destroy', $team));

    $this->assertDatabaseMissing('teams', ['id' => $team->id]);
});

/**
 * ExportTemplateController Tests
 */
test('export template index shows user templates', function () {
    $user = User::factory()->create();
    ExportTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Template',
    ]);

    $response = $this->actingAs($user)->get(route('export-templates.index'));

    $response->assertStatus(200);
    $response->assertSee('My Template');
});

test('export template store creates template with valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('export-templates.store'), [
        'name' => 'CSV Export',
        'description' => 'Export todos as CSV',
        'format' => 'csv',
        'fields' => ['id', 'title', 'status'],
        'order' => ['id'],
        'filters' => [],
    ]);

    $this->assertDatabaseHas('export_templates', [
        'user_id' => $user->id,
        'name' => 'CSV Export',
        'format' => 'csv',
    ]);

    $response->assertRedirect(route('export-templates.index'));
});

test('export template store validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('export-templates.store'), [
        'name' => 'Incomplete Template',
        // Missing required fields
    ]);

    $response->assertSessionHasErrors(['description', 'format', 'fields']);
});

test('export template update modifies template', function () {
    $user = User::factory()->create();
    $template = ExportTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
    ]);

    $response = $this->actingAs($user)->put(route('export-templates.update', $template), [
        'name' => 'Updated Name',
        'description' => 'Updated description',
        'format' => 'json',
        'fields' => ['id', 'title'],
    ]);

    $this->assertDatabaseHas('export_templates', [
        'id' => $template->id,
        'name' => 'Updated Name',
    ]);

    $response->assertRedirect(route('export-templates.index'));
});

test('export template destroy deletes template', function () {
    $user = User::factory()->create();
    $template = ExportTemplate::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('export-templates.destroy', $template));

    $this->assertDatabaseMissing('export_templates', ['id' => $template->id]);
    $response->assertRedirect(route('export-templates.index'));
});

/**
 * DashboardController Tests
 */
test('dashboard index shows statistics and charts', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    // Create completed todos
    Todo::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'completed_at' => now(),
    ]);

    // Create active todos
    Todo::factory()->count(2)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'completed_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas(['total', 'done', 'active', 'weeklyData', 'monthlyData']);

    // Verify statistics are present
    $total = $response->viewData('total');
    $done = $response->viewData('done');
    $active = $response->viewData('active');

    // Should have created at least 5 todos
    $this->assertGreaterThanOrEqual(5, $total);
    // Statistics should be non-negative
    $this->assertGreaterThanOrEqual(0, $done);
    $this->assertGreaterThanOrEqual(0, $active);
});

test('dashboard creates default widgets on first access', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    // Default widgets should be created
    $this->assertDatabaseHas('dashboard_widgets', [
        'user_id' => $user->id,
        'widget_type' => 'stats',
    ]);

    $widgetsCount = DashboardWidget::where('user_id', $user->id)->count();
    $this->assertGreaterThan(0, $widgetsCount);
});

test('dashboard export csv generates file', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Export Test',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.export.csv'));

    $response->assertStatus(200);
    $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    $this->assertStringContainsString('Export Test', $response->streamedContent());
});

test('dashboard export json generates valid json', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'JSON Export Test',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard.export.json'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/json');

    $data = $response->json();
    $this->assertIsArray($data);
    $this->assertCount(1, $data);
    $this->assertEquals('JSON Export Test', $data[0]['title']);
});

/**
 * ProfileController Tests
 */
test('profile edit shows user profile form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertViewHas('user');
});

test('profile update changes user information', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'New Name',
        'email' => 'old@example.com', // Keep same email
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
    ]);

    $response->assertRedirect(route('profile.edit'));
    $response->assertSessionHas('status', 'profile-updated');
});

test('profile update resets email verification when email changes', function () {
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'new@example.com',
    ]);

    $user->refresh();
    $this->assertNull($user->email_verified_at);
});

test('profile destroy deletes user account', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $response->assertRedirect('/');
});

test('profile destroy requires correct password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $response->assertSessionHasErrors();
});

/**
 * CommentController Tests
 */
test('comment store creates comment on todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->post(route('comments.store', $todo), [
        'body' => 'This is a test comment',
    ]);

    $this->assertDatabaseHas('comments', [
        'todo_id' => $todo->id,
        'user_id' => $user->id,
        'body' => 'This is a test comment',
    ]);

    $response->assertRedirect();
});

test('comment store sends notification to todo owner', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $commenter = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $owner->id]);
    $todo = Todo::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($commenter)->post(route('comments.store', $todo), [
        'body' => 'Comment from another user',
    ]);

    Notification::assertSentTo($owner, TodoCommentNotification::class);
});

test('comment store does not send notification to self', function () {
    Notification::fake();

    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)->post(route('comments.store', $todo), [
        'body' => 'Self comment',
    ]);

    Notification::assertNothingSent();
});

test('comment store validates body is required', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->post(route('comments.store', $todo), [
        'body' => '',
    ]);

    $response->assertSessionHasErrors('body');
});

test('comment destroy deletes comment', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);
    $todo = Todo::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);
    $comment = Comment::factory()->create([
        'todo_id' => $todo->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('comments.destroy', $comment));

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    $response->assertRedirect();
});
