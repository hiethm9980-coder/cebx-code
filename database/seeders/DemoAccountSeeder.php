<?php
namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use App\Models\Role;
use App\Models\Organization;
use App\Models\Wallet;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::where('slug', 'demo-company')->first() ?? Account::first();
        if (!$account) {
            $account = Account::firstOrCreate(
                ['slug' => 'demo-company'],
                ['name' => 'شركة الشحن السريع', 'type' => 'organization', 'status' => 'active']
            );
        }

        Organization::firstOrCreate(
            ['account_id' => $account->id, 'legal_name' => 'شركة الشحن السريع'],
            [
                'trade_name' => 'شركة الشحن السريع',
                'country_code' => 'SA',
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]
        );

        Wallet::firstOrCreate(
            ['account_id' => $account->id],
            [
                'currency' => 'SAR',
                'available_balance' => 45230.00,
                'locked_balance' => 3200.00,
                'status' => 'active',
            ]
        );

        $adminRole = Role::where('account_id', $account->id)->where('name', 'super-admin')->first();
        $operatorRole = Role::where('account_id', $account->id)->where('name', 'operator')->first();
        $viewerRole = Role::where('account_id', $account->id)->where('name', 'viewer')->first();
        $financeRole = Role::where('account_id', $account->id)->where('name', 'finance')->first();

        $users = [
            ['name' => 'أحمد المحمدي', 'email' => 'admin@company.sa', 'password' => Hash::make('password'), 'role_id' => $adminRole?->id, 'status' => 'active', 'phone' => '+966501234567', 'locale' => 'ar'],
            ['name' => 'فاطمة العلي', 'email' => 'fatima@company.sa', 'password' => Hash::make('password'), 'role_id' => $operatorRole?->id, 'status' => 'active', 'phone' => '+966507654321', 'locale' => 'ar'],
            ['name' => 'خالد السعيد', 'email' => 'khalid@company.sa', 'password' => Hash::make('password'), 'role_id' => $financeRole?->id, 'status' => 'active', 'phone' => '+966509876543', 'locale' => 'ar'],
            ['name' => 'نورا الشمري', 'email' => 'noura@company.sa', 'password' => Hash::make('password'), 'role_id' => $viewerRole?->id, 'status' => 'active', 'phone' => '+966503456789', 'locale' => 'ar'],
        ];

        foreach ($users as $u) {
            $roleId = $u['role_id'] ?? null;
            unset($u['role_id']);
            $user = User::firstOrCreate(
                ['account_id' => $account->id, 'email' => $u['email']],
                array_merge($u, ['account_id' => $account->id, 'email_verified_at' => now()])
            );
            if ($roleId && $user->wasRecentlyCreated) {
                $user->roles()->syncWithoutDetaching([$roleId => ['assigned_at' => now()]]);
            }
        }

        $addresses = [
            ['label' => 'المقر الرئيسي', 'contact_name' => 'شركة الشحن السريع', 'phone' => '+966501234567', 'address_line_1' => 'طريق الملك فهد', 'city' => 'الرياض', 'postal_code' => '11564', 'country' => 'SA', 'is_default_sender' => true],
            ['label' => 'فرع جدة', 'contact_name' => 'فرع جدة', 'phone' => '+966507654321', 'address_line_1' => 'شارع فلسطين', 'city' => 'جدة', 'postal_code' => '21462', 'country' => 'SA', 'is_default_sender' => false],
            ['label' => 'مستودع الدمام', 'contact_name' => 'مستودع الدمام', 'phone' => '+966509876543', 'address_line_1' => 'شارع 15', 'city' => 'الدمام', 'postal_code' => '31473', 'country' => 'SA', 'is_default_sender' => false],
        ];

        foreach ($addresses as $a) {
            Address::firstOrCreate(
                ['account_id' => $account->id, 'label' => $a['label']],
                array_merge($a, ['account_id' => $account->id])
            );
        }
    }
}
