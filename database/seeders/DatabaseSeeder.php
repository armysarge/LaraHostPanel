<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // TODO: Change these default credentials before deploying to production.
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@larahostpanel.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
    }
}
