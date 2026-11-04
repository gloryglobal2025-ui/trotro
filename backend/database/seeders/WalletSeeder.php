<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            if (! $user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'bonus_balance' => 0.00,
                    'pending_balance' => 0.00,
                ]);
            }
        }
    }
}
