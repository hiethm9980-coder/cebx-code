<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates one account and one user for local/dev login.
 * Email: admin@company.sa  Password: password
 */
class DevLoginSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::firstOrCreate(
            ['slug' => 'demo-company'],
            [
                'name' => 'شركة الشحن السريع',
                'type' => 'organization',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['account_id' => $account->id, 'email' => 'admin@company.sa'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password'),
                'status' => 'active',
                'is_owner' => true,
                'locale' => 'ar',
                'timezone' => 'Asia/Riyadh',
                'email_verified_at' => now(),
            ]
        );
    }
}
