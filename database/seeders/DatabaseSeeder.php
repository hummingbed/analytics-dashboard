<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = ['Ada Johnson', 'Michael Chen', 'Sarah Williams', 'David Okafor', 'Amara Bello'];
        $descriptions = ['Wallet deposit', 'Online purchase', 'Subscription payment', 'Account transfer'];

        foreach (range(1, 18) as $index) {
            Transaction::create([
                'transaction_id' => (string) Str::uuid(),
                'user_name' => $users[array_rand($users)],
                'amount' => random_int(1500, 150000) / 100,
                'type' => random_int(0, 1) ? 'credit' : 'debit',
                'status' => fake()->randomElement(['successful', 'successful', 'successful', 'pending', 'failed']),
                'description' => $descriptions[array_rand($descriptions)],
                'transacted_at' => now()->subMinutes(random_int(0, 600)),
            ]);
        }
    }
}
