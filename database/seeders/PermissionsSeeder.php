<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Rbac\PermissionsCatalog;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionsCatalog::all() as $group => $permissions) {
            foreach ($permissions as $key => $displayName) {
                Permission::updateOrCreate(
                    ['key' => $key],
                    [
                        'group'        => $group,
                        'display_name' => $displayName,
                        'description'  => $displayName,
                    ]
                );
            }
        }

        $this->command->info('✅ Seeded ' . count(PermissionsCatalog::keys()) . ' permissions.');
    }
}
