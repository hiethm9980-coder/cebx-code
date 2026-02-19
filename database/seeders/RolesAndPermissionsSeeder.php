<?php
namespace Database\Seeders;

use App\Models\Account;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PermissionCatalog;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::where('slug', 'demo-company')->first();
        if (!$account) {
            $account = Account::first();
        }
        if (!$account) {
            $this->command->warn('No account found. Run DevLoginSeeder first.');
            return;
        }

        $modules = [
            'shipments' => ['create', 'read', 'update', 'delete', 'cancel', 'bulk_import', 'print_label', 'create_return'],
            'orders'    => ['create', 'read', 'update', 'delete', 'cancel', 'ship'],
            'stores'    => ['create', 'read', 'delete', 'sync', 'test_connection'],
            'tracking'  => ['read', 'subscribe', 'manual_poll'],
            'wallet'    => ['read', 'topup', 'hold', 'capture', 'reconcile', 'statement'],
            'users'     => ['create', 'read', 'update', 'delete', 'toggle_status'],
            'roles'     => ['create', 'read', 'delete', 'assign', 'revoke'],
            'invitations' => ['create', 'read', 'cancel', 'resend'],
            'notifications' => ['read', 'manage_templates', 'manage_channels', 'manage_preferences'],
            'reports'   => ['read', 'export', 'save', 'schedule'],
            'audit_log' => ['read', 'export'],
            'kyc'       => ['read', 'upload', 'submit_review'],
            'organizations' => ['create', 'read', 'manage_members'],
            'support'   => ['create', 'read', 'reply', 'resolve', 'assign'],
            'addresses' => ['create', 'read', 'delete', 'set_default'],
            'settings'  => ['read', 'update'],
            'admin'     => ['system_health', 'api_keys', 'feature_flags'],
            'pricing'   => ['read', 'create', 'calculate'],
            'dg'        => ['read', 'create_declaration'],
            'financial' => ['read'],
            'containers'=> ['create', 'read', 'detail'],
            'customs'   => ['create', 'read', 'clear'],
            'drivers'   => ['create', 'read', 'toggle_status'],
            'claims'    => ['create', 'read', 'resolve'],
            'branches'  => ['read'],
            'companies' => ['read'],
            'vessels'   => ['read'],
            'hs_codes'  => ['search'],
        ];

        $permissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $key = "{$module}.{$action}";
                $perm = Permission::firstOrCreate(
                    ['key' => $key],
                    [
                        'group' => $module,
                        'display_name' => "Permission to {$action} in {$module}",
                        'description' => "Permission to {$action} in {$module}",
                    ]
                );
                $permissions[$key] = $perm;

                $category = in_array($module, ['wallet', 'financial', 'reports', 'pricing']) ? 'financial' : ($module === 'admin' ? 'admin' : 'operational');
                PermissionCatalog::firstOrCreate(
                    ['key' => $key],
                    [
                        'name' => $this->getArabicDesc($module, $action),
                        'description' => "Permission to {$action} in {$module}",
                        'module' => $module,
                        'category' => $category,
                    ]
                );
            }
        }

        $superAdmin = Role::firstOrCreate(
            ['account_id' => $account->id, 'name' => 'super-admin'],
            ['display_name' => 'مدير النظام', 'is_system' => true]
        );
        $admin = Role::firstOrCreate(
            ['account_id' => $account->id, 'name' => 'admin'],
            ['display_name' => 'مدير', 'is_system' => true]
        );
        $operator = Role::firstOrCreate(
            ['account_id' => $account->id, 'name' => 'operator'],
            ['display_name' => 'مشغّل', 'is_system' => true]
        );
        $viewer = Role::firstOrCreate(
            ['account_id' => $account->id, 'name' => 'viewer'],
            ['display_name' => 'عارض', 'is_system' => true]
        );
        $finance = Role::firstOrCreate(
            ['account_id' => $account->id, 'name' => 'finance'],
            ['display_name' => 'مالية', 'is_system' => false]
        );

        $superAdmin->permissions()->sync(collect($permissions)->pluck('id')->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->toArray());
        $adminPerms = collect($permissions)->filter(fn ($p, $k) => !str_starts_with($k, 'admin.'))->pluck('id')->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->toArray();
        $admin->permissions()->sync($adminPerms);
        $opModules = ['shipments', 'orders', 'stores', 'tracking', 'support', 'addresses', 'notifications'];
        $opPerms = collect($permissions)->filter(fn ($p, $k) => in_array(explode('.', $k)[0], $opModules))->pluck('id')->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->toArray();
        $operator->permissions()->sync($opPerms);
        $viewPerms = collect($permissions)->filter(fn ($p, $k) => str_ends_with($k, '.read') || str_ends_with($k, '.search'))->pluck('id')->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->toArray();
        $viewer->permissions()->sync($viewPerms);
        $finModules = ['wallet', 'financial', 'reports', 'pricing'];
        $finPerms = collect($permissions)->filter(fn ($p, $k) => in_array(explode('.', $k)[0], $finModules))->pluck('id')->mapWithKeys(fn ($id) => [$id => ['granted_at' => now()]])->toArray();
        $finance->permissions()->sync($finPerms);
    }

    private function getArabicDesc(string $module, string $action): string
    {
        $mods = ['shipments'=>'الشحنات','orders'=>'الطلبات','stores'=>'المتاجر','tracking'=>'التتبع','wallet'=>'المحفظة','users'=>'المستخدمين','roles'=>'الأدوار','invitations'=>'الدعوات','notifications'=>'الإشعارات','reports'=>'التقارير','audit_log'=>'سجل التدقيق','kyc'=>'التحقق','organizations'=>'المنظمات','support'=>'الدعم','addresses'=>'العناوين','settings'=>'الإعدادات','admin'=>'الإدارة','pricing'=>'التسعير','dg'=>'المواد الخطرة','financial'=>'المالية','containers'=>'الحاويات','customs'=>'الجمارك','drivers'=>'السائقين','claims'=>'المطالبات','branches'=>'الفروع','companies'=>'الشركات','vessels'=>'السفن','hs_codes'=>'الأكواد الجمركية'];
        $acts = ['create'=>'إنشاء','read'=>'عرض','update'=>'تعديل','delete'=>'حذف','cancel'=>'إلغاء','search'=>'بحث'];
        return ($acts[$action] ?? $action) . ' ' . ($mods[$module] ?? $module);
    }
}
