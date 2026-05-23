<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Dusk\Browser;

uses(RefreshDatabase::class);

test('user can login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->browse(function (Browser $browser) use ($user) {
        $browser->visit('/login')
            ->type('email', $user->email)
            ->type('password', 'password')
            ->press('Log in')
            ->assertPathIs('/todos')
            ->assertAuthenticated();
    });
});

test('user can logout', function () {
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/todos')
            ->press('form[action="/logout"] button')
            ->assertPathIs('/')
            ->assertGuest();
    });
});

test('user cannot login with invalid credentials', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->type('email', 'invalid@example.com')
            ->type('password', 'wrongpassword')
            ->press('Log in')
            ->assertPathIs('/login')
            ->assertSee('These credentials do not match our records');
    });
});
