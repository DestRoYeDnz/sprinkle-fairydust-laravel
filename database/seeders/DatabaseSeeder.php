<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
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

        $this->call(QuoteSeeder::class);
    }
}
