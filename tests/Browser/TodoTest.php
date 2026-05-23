<?php

use App\Models\User;
use App\Models\Category;
use Laravel\Dusk\Browser;

test('user can create a todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $category) {
        $browser->loginAs($user)
            ->visit('/todos/create')
            ->type('title', 'Buy groceries')
            ->type('description', 'Milk, bread, eggs')
            ->select('category_id', $category->id)
            ->select('priority', 'medium')
            ->press('Save')
            ->assertPathIs('/todos')
            ->assertSee('Buy groceries');
    });
});

test('user can view todo details', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $category) {
        // Create a todo first
        $browser->loginAs($user)
            ->visit('/todos/create')
            ->type('title', 'Test Todo')
            ->type('description', 'Test description')
            ->select('category_id', $category->id)
            ->press('Save')
            ->assertPathIs('/todos');

        // Click on the first todo to view details
        $browser->clickLink('Test Todo')
            ->assertSee('Test description')
            ->assertSee($category->name);
    });
});

test('user can mark todo as completed', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $category) {
        // Create a todo
        $browser->loginAs($user)
            ->visit('/todos/create')
            ->type('title', 'Complete this task')
            ->select('category_id', $category->id)
            ->press('Save')
            ->assertPathIs('/todos');

        // Mark as completed by clicking toggle button
        $browser->press('button[title="Mark as complete"]')
            ->waitForText('Completed')
            ->assertSee('Completed');
    });
});

test('user can delete a todo', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $category) {
        // Create a todo
        $browser->loginAs($user)
            ->visit('/todos/create')
            ->type('title', 'Delete this task')
            ->select('category_id', $category->id)
            ->press('Save')
            ->assertPathIs('/todos')
            ->assertSee('Delete this task');

        // Delete the todo
        $browser->press('button[title="Delete"]')
            ->whenAvailable('@confirm-delete-modal', function ($modal) {
                $modal->press('Confirm');
            })
            ->waitUntilMissing('Delete this task')
            ->assertDontSee('Delete this task');
    });
});

test('user can filter todos by category', function () {
    $user = User::factory()->create();
    $category1 = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Work',
    ]);
    $category2 = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Personal',
    ]);

    $this->browse(function (Browser $browser) use ($user, $category1, $category2) {
        // Create two todos with different categories
        $browser->loginAs($user)
            ->visit('/todos/create')
            ->type('title', 'Work task')
            ->select('category_id', $category1->id)
            ->press('Save')
            ->visit('/todos/create')
            ->type('title', 'Personal task')
            ->select('category_id', $category2->id)
            ->press('Save')
            ->assertPathIs('/todos');

        // Filter by Work category
        $browser->select('filter_category', $category1->id)
            ->waitFor('.todo-list')
            ->assertSee('Work task')
            ->assertDontSee('Personal task');

        // Filter by Personal category
        $browser->select('filter_category', $category2->id)
            ->waitFor('.todo-list')
            ->assertSee('Personal task')
            ->assertDontSee('Work task');
    });
});
