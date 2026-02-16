<?php

use App\Models\User;

it('allows admin users to access all admin pages', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $routes = [
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

    foreach ($routes as $route) {
        $this->actingAs($admin)
            ->get(route($route))
            ->assertOk();
    }
});

it('forbids non-admin users from all admin pages', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $routes = [
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

    foreach ($routes as $route) {
        $this->actingAs($user)
            ->get(route($route))
            ->assertForbidden();
    }
});
