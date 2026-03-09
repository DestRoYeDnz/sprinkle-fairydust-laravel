<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'brettj@dekode.co.nz'],
            [
                'name' => 'Brett J',
                'password' => 'y3hg8bzr',
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );
    }
}
