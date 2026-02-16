<?php

use App\Models\User;

test('admin users can create users from admin panel', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->from('/admin')
        ->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_admin' => true,
        ]);

    $response->assertRedirect('/admin');

    $this->assertDatabaseHas('users', [
        'email' => 'new-user@example.com',
        'is_admin' => true,
    ]);
});

test('non-admin users can not create users from admin panel', function () {
    $user = User::factory()->create([
        'is_admin' => false,
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.users.store'), [
            'name' => 'No Access',
            'email' => 'no-access@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('users', [
        'email' => 'no-access@example.com',
    ]);
});

