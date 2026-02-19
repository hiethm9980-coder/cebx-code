<?php

namespace App\Rbac;

/**
 * PermissionsCatalog
 *
 * The single source of truth for all permissions in the system.
 * No permission can be granted outside this catalog (FR-IAM-003 business rule).
 *
 * Format: 'group:action' => 'Description'
 */
class PermissionsCatalog
{
    /**
     * All permissions grouped by module.
     */
    public static function all(): array
    {
        return [
            // ── Users & Account ──────────────────────────────────
            'users' => [
                'users:view'    => 'عرض قائمة المستخدمين',
                'users:manage'  => 'إضافة/تعطيل/حذف المستخدمين',
                'users:invite'  => 'دعوة مستخدمين جدد',
            ],

            // ── Roles & Permissions ──────────────────────────────
            'roles' => [
                'roles:view'   => 'عرض الأدوار والصلاحيات',
                'roles:manage' => 'إنشاء/تعديل/حذف الأدوار',
                'roles:assign' => 'تعيين أدوار للمستخدمين',
            ],

            // ── Account Settings ─────────────────────────────────
            'account' => [
                'account:view'     => 'عرض إعدادات الحساب',
                'account:manage'   => 'تعديل إعدادات الحساب',
            ],

            // ── Shipments ────────────────────────────────────────
            'shipments' => [
                'shipments:view'    => 'عرض الشحنات',
                'shipments:create'  => 'إنشاء شحنة جديدة',
                'shipments:edit'    => 'تعديل الشحنات',
                'shipments:cancel'  => 'إلغاء الشحنات',
                'shipments:print'   => 'طباعة بوليصات الشحن',
                'shipments:export'  => 'تصدير بيانات الشحنات',
            ],

            // ── Orders ───────────────────────────────────────────
            'orders' => [
                'orders:view'    => 'عرض الطلبات',
                'orders:manage'  => 'إدارة الطلبات',
                'orders:export'  => 'تصدير بيانات الطلبات',
            ],

            // ── Stores ───────────────────────────────────────────
            'stores' => [
                'stores:view'   => 'عرض المتاجر',
                'stores:manage' => 'إدارة المتاجر وقنوات البيع',
            ],

            // ── Financial ────────────────────────────────────────
            'financial' => [
                'financial:view'           => 'عرض البيانات المالية العامة',
                'financial:profit.view'    => 'عرض بيانات الربح والتكلفة الصافية (Net/Retail/Profit)',
                'financial:cards.view'     => 'عرض بيانات بطاقات الدفع (غير مخفية)',
                'financial:wallet_topup'   => 'شحن المحفظة',
                'financial:wallet_view'    => 'عرض رصيد المحفظة',
                'financial:ledger_view'    => 'عرض كشف الحساب',
                'financial:invoices_view'  => 'عرض الفواتير',
                'financial:invoices_manage'=> 'إدارة الفواتير',
                'financial:refund_review'  => 'مراجعة طلبات الاسترداد',
                'financial:threshold'      => 'ضبط حدود التنبيه المالي',
            ],

            // ── Reports ──────────────────────────────────────────
            'reports' => [
                'reports:view'    => 'عرض التقارير',
                'reports:export'  => 'تصدير التقارير',
                'reports:create'  => 'إنشاء تقارير مخصصة',
            ],

            // ── KYC ──────────────────────────────────────────────
            'kyc' => [
                'kyc:view'     => 'عرض حالة التحقق',
                'kyc:manage'   => 'إدارة وثائق التحقق',
                'kyc:documents'=> 'الوصول لوثائق KYC الحساسة',
            ],

            // ── API Keys ─────────────────────────────────────────
            'apikeys' => [
                'apikeys:view'   => 'عرض مفاتيح API',
                'apikeys:manage' => 'إنشاء/إلغاء مفاتيح API',
            ],

            // ── Audit Logs ───────────────────────────────────────
            'audit' => [
                'audit:view'   => 'عرض سجلات التدقيق',
                'audit:export' => 'تصدير سجلات التدقيق',
            ],
        ];
    }

    /**
     * Flat list of all permission keys.
     */
    public static function keys(): array
    {
        $keys = [];
        foreach (static::all() as $group => $permissions) {
            $keys = array_merge($keys, array_keys($permissions));
        }
        return $keys;
    }

    /**
     * Check if a permission key exists in the catalog.
     */
    public static function exists(string $key): bool
    {
        return in_array($key, static::keys(), true);
    }

    /**
     * Get all groups.
     */
    public static function groups(): array
    {
        return array_keys(static::all());
    }

    // ─── Role Templates ──────────────────────────────────────────

    /**
     * Predefined role templates with suggested permissions.
     */
    public static function templates(): array
    {
        return [
            'admin' => [
                'display_name' => 'مدير النظام',
                'description'  => 'صلاحيات كاملة باستثناء حذف الحساب',
                'permissions'  => static::keys(), // All permissions
            ],

            'accountant' => [
                'display_name' => 'محاسب',
                'description'  => 'إدارة المالية والفواتير',
                'permissions'  => [
                    'financial:view', 'financial:profit.view', 'financial:cards.view',
                    'financial:wallet_view', 'financial:ledger_view',
                    'financial:invoices_view', 'financial:invoices_manage',
                    'financial:refund_review', 'financial:threshold',
                    'reports:view', 'reports:export',
                    'audit:view',
                ],
            ],

            'warehouse' => [
                'display_name' => 'مدير المستودع',
                'description'  => 'إدارة الشحنات والطلبات',
                'permissions'  => [
                    'shipments:view', 'shipments:create', 'shipments:edit',
                    'shipments:print', 'shipments:export',
                    'orders:view', 'orders:manage', 'orders:export',
                    'stores:view',
                ],
            ],

            'viewer' => [
                'display_name' => 'مشاهد',
                'description'  => 'صلاحيات العرض فقط',
                'permissions'  => [
                    'users:view', 'roles:view', 'account:view',
                    'shipments:view', 'orders:view', 'stores:view',
                    'reports:view', 'kyc:view', 'audit:view',
                ],
            ],

            'printer' => [
                'display_name' => 'طباعة فقط',
                'description'  => 'طباعة بوليصات الشحن فقط — بدون بيانات مالية',
                'permissions'  => [
                    'shipments:view', 'shipments:print',
                    'orders:view',
                ],
            ],
        ];
    }

    /**
     * Get a specific template.
     */
    public static function template(string $name): ?array
    {
        return static::templates()[$name] ?? null;
    }
}
