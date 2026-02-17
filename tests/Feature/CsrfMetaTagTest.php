<?php

use App\Models\User;

it('includes the csrf meta token in the web app shell', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('name="csrf-token"', false);
});

it('includes the csrf meta token on all admin pages', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $adminRoutes = [
        'admin',
        'admin.events',
        'admin.images',
        'admin.quotes',
        'admin.testimonials',
        'admin.users.create',
        'admin.calculator',
        'admin.settings',
        'admin.tracking',
    ];

    foreach ($adminRoutes as $routeName) {
        $this->actingAs($admin)
            ->get(route($routeName))
            ->assertOk()
            ->assertSee('name="csrf-token"', false);
    }
});

it('can refresh csrf token through the csrf token endpoint', function () {
    $response = $this->get(route('csrf.token'));

    $response->assertOk();
    $response->assertJsonStructure(['token']);
});
