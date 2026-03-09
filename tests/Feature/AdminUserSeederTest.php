<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;

test('admin user seeder creates the default admin account', function () {
    $this->seed(AdminUserSeeder::class);

    $adminUser = User::query()
        ->where('email', 'brettj@dekode.co.nz')
        ->firstOrFail();

    expect($adminUser->name)->toBe('Brett J')
        ->and($adminUser->is_admin)->toBeTrue()
        ->and($adminUser->email_verified_at)->not->toBeNull();
});
