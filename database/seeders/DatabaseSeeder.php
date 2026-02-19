<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DevLoginSeeder::class,
            RolesAndPermissionsSeeder::class,
            SystemSettingsSeeder::class,
            DemoAccountSeeder::class,
            CarrierSeeder::class,
            DhlStatusMappingSeeder::class,
            HsCodeSeeder::class,
            FeatureFlagSeeder::class,
            NotificationTemplateSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
