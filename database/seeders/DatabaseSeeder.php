<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'demo@example.com'], ['name' => 'Indra', 'password' => Hash::make('SalesDemo!2026')]);
        $names = ['Olivia Martin', 'Jackson Lee', 'Sofia Chen', 'Noah Williams', 'Emma Davis', 'Liam Wilson', 'Ava Thompson', 'Ethan Park'];
        $products = ['Studio Headphones', 'Everyday Backpack', 'Desk Lamp', 'Mechanical Keyboard', 'Canvas Sneakers', 'Travel Bottle'];
        for ($i = 0; $i < 180; $i++) {
            Sale::firstOrCreate(['reference' => sprintf('ORD-%05d', 10480 + $i)], [
                'customer' => $names[$i % 8], 'product' => $products[$i % 6], 'channel' => ['Online store', 'Marketplace', 'Retail'][$i % 3],
                'status' => ($i % 11 === 0 ? 'Refunded' : ($i % 7 === 0 ? 'Pending' : 'Paid')),
                'ordered_at' => today()->subDays(intdiv($i, 3))->toDateString(), 'amount_cents' => [12900, 8900, 6500, 15900, 9900, 3500][$i % 6] + ($i % 5) * 1200,
            ]);
        }
    }
}
