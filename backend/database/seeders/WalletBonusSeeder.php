<?php

namespace Database\Seeders;

use App\Models\WalletBonus;
use Illuminate\Database\Seeder;

class WalletBonusSeeder extends Seeder
{
    public function run(): void
    {
        WalletBonus::create([
            'name' => 'Welcome Bonus',
            'code' => 'WELCOME2024',
            'amount' => 20.00,
            'type' => 'signup',
            'min_deposit' => 0,
            'min_wallet_balance' => 0,
            'usage_limit' => null,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(6),
            'is_active' => true,
            'description' => 'Free bonus for new users signing up',
        ]);

        WalletBonus::create([
            'name' => 'Referral Bonus',
            'code' => 'REFERFRIEND',
            'amount' => 15.00,
            'type' => 'referral',
            'min_deposit' => 0,
            'min_wallet_balance' => 0,
            'usage_limit' => null,
            'valid_from' => now(),
            'valid_until' => null,
            'is_active' => true,
            'description' => 'Bonus for referring new users',
        ]);

        WalletBonus::create([
            'name' => 'Weekend Special',
            'code' => 'WEEKEND50',
            'amount' => 50.00,
            'type' => 'promotional',
            'min_deposit' => 100,
            'min_wallet_balance' => 0,
            'usage_limit' => 500,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(3),
            'is_active' => true,
            'description' => 'Special weekend promotional bonus',
        ]);

        WalletBonus::create([
            'name' => 'Holiday Bonus',
            'code' => 'HOLIDAY25',
            'amount' => 25.00,
            'type' => 'special',
            'min_deposit' => 50,
            'min_wallet_balance' => 0,
            'usage_limit' => 1000,
            'valid_from' => now(),
            'valid_until' => now()->addMonth(),
            'is_active' => true,
            'description' => 'Special holiday bonus',
        ]);
    }
}
