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
        // Create test users with balances for testing transactions
        User::factory()->create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
            'balance' => 1000.00,
        ]);

        User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
            'balance' => 500.00,
        ]);

        User::factory()->create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'password' => bcrypt('password'),
            'balance' => 750.00,
        ]);

        User::factory()->create([
            'name' => 'Diana Prince',
            'email' => 'diana@example.com',
            'password' => bcrypt('password'),
            'balance' => 250.00,
        ]);

        User::factory()->create([
            'name' => 'Eve Anderson',
            'email' => 'eve@example.com',
            'password' => bcrypt('password'),
            'balance' => 1500.00,
        ]);
    }
}
