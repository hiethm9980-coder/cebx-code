<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'بوابة إدارة الشحن'); ?> — Shipping Gateway</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
<div class="app-layout">
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <?php if($portalType === 'b2c'): ?>
                <div class="sidebar-logo" style="background:linear-gradient(135deg,#0D9488,#065F56)">B2C</div>
                <div class="sidebar-info">
                    <span class="sidebar-title">بوابة الشحن</span>
                    <span class="sidebar-subtitle">للأفراد</span>
                </div>
            <?php elseif($portalType === 'admin'): ?>
                <div class="sidebar-logo" style="background:linear-gradient(135deg,#7C3AED,#4C1D95)">SYS</div>
                <div class="sidebar-info">
                    <span class="sidebar-title">Shipping Gateway</span>
                    <span class="sidebar-subtitle">لوحة مدير النظام</span>
                </div>
            <?php else: ?>
                <div class="sidebar-logo">B2B</div>
                <div class="sidebar-info">
                    <span class="sidebar-title">Shipping Gateway</span>
                    <span class="sidebar-subtitle">بوابة الأعمال</span>
                </div>
            <?php endif; ?>
        </div>

        <nav class="sidebar-nav">
            <?php
                $currentRoute = Route::currentRouteName() ?? '';
                $unreadNotifs = \App\Models\Notification::where('read_at', null)->count();
                $openTickets = \App\Models\SupportTicket::where('status', 'open')->count();
                $processingShipments = \App\Models\Shipment::whereIn('status', ['payment_pending','purchased','picked_up','in_transit','out_for_delivery'])->count();

                $b2cMenu = [
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '🏠', 'label' => 'الرئيسية'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'شحناتي', 'badge' => $processingShipments],
                    ['id' => 'tracking', 'route' => 'tracking.index', 'icon' => '🔍', 'label' => 'التتبع'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['d' => true],
                    ['id' => 'addresses', 'route' => 'addresses.index', 'icon' => '📒', 'label' => 'العناوين'],
                    ['id' => 'support', 'route' => 'support.index', 'icon' => '🎧', 'label' => 'الدعم', 'badge' => $openTickets],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙️', 'label' => 'الإعدادات'],
                ];

                $b2bMenu = [
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '📊', 'label' => 'لوحة التحكم'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'الشحنات', 'badge' => $processingShipments],
                    ['id' => 'orders', 'route' => 'orders.index', 'icon' => '🛒', 'label' => 'الطلبات'],
                    ['id' => 'stores', 'route' => 'stores.index', 'icon' => '🏪', 'label' => 'المتاجر'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['id' => 'reports', 'route' => 'reports.index', 'icon' => '📊', 'label' => 'التقارير'],
                    ['d' => true],
                    ['id' => 'users', 'route' => 'users.index', 'icon' => '👥', 'label' => 'المستخدمون'],
                    ['id' => 'roles', 'route' => 'roles.index', 'icon' => '🔐', 'label' => 'الأدوار'],
                    ['id' => 'invitations', 'route' => 'invitations.index', 'icon' => '📨', 'label' => 'الدعوات'],
                    ['d' => true],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙️', 'label' => 'الإعدادات'],
                ];

                $adminMenu = [
                    ['g' => 'العمليات'],
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '📊', 'label' => 'لوحة التحكم'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'الشحنات', 'badge' => $processingShipments],
                    ['id' => 'orders', 'route' => 'orders.index', 'icon' => '🛒', 'label' => 'الطلبات'],
                    ['id' => 'tracking', 'route' => 'tracking.index', 'icon' => '🔍', 'label' => 'التتبع'],
                    ['id' => 'stores', 'route' => 'stores.index', 'icon' => '🏪', 'label' => 'المتاجر'],

                    ['g' => 'المالية'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['id' => 'financial', 'route' => 'financial.index', 'icon' => '💳', 'label' => 'المالية'],
                    ['id' => 'pricing', 'route' => 'pricing.index', 'icon' => '🏷️', 'label' => 'التسعير'],

                    ['g' => 'اللوجستيات'],
                    ['id' => 'containers', 'route' => 'containers.index', 'icon' => '🚢', 'label' => 'الحاويات'],
                    ['id' => 'customs', 'route' => 'customs.index', 'icon' => '🛃', 'label' => 'الجمارك'],
                    ['id' => 'vessels', 'route' => 'vessels.index', 'icon' => '⛴️', 'label' => 'السفن'],
                    ['id' => 'schedules', 'route' => 'schedules.index', 'icon' => '📅', 'label' => 'الجداول'],
                    ['id' => 'drivers', 'route' => 'drivers.index', 'icon' => '🚛', 'label' => 'السائقين'],
                    ['id' => 'hscodes', 'route' => 'hscodes.index', 'icon' => '🔢', 'label' => 'أكواد HS'],

                    ['g' => 'الامتثال'],
                    ['id' => 'kyc', 'route' => 'kyc.index', 'icon' => '🪪', 'label' => 'KYC'],
                    ['id' => 'dg', 'route' => 'dg.index', 'icon' => '☣️', 'label' => 'البضائع الخطرة'],
                    ['id' => 'risk', 'route' => 'risk.index', 'icon' => '⚠️', 'label' => 'المخاطر'],
                    ['id' => 'claims', 'route' => 'claims.index', 'icon' => '📋', 'label' => 'المطالبات'],

                    ['g' => 'الإدارة'],
                    ['id' => 'organizations', 'route' => 'organizations.index', 'icon' => '🏢', 'label' => 'المنظمات'],
                    ['id' => 'companies', 'route' => 'companies.index', 'icon' => '🏭', 'label' => 'الشركات'],
                    ['id' => 'branches', 'route' => 'branches.index', 'icon' => '🏬', 'label' => 'الفروع'],
                    ['id' => 'users', 'route' => 'users.index', 'icon' => '👥', 'label' => 'المستخدمون'],
                    ['id' => 'roles', 'route' => 'roles.index', 'icon' => '🔐', 'label' => 'الأدوار'],
                    ['id' => 'invitations', 'route' => 'invitations.index', 'icon' => '📨', 'label' => 'الدعوات'],

                    ['g' => 'النظام'],
                    ['id' => 'admin', 'route' => 'admin.index', 'icon' => '🛡️', 'label' => 'الإدارة العامة'],
                    ['id' => 'audit', 'route' => 'audit.index', 'icon' => '📜', 'label' => 'التدقيق'],
                    ['id' => 'reports', 'route' => 'reports.index', 'icon' => '📈', 'label' => 'التقارير'],
                    ['id' => 'notifications', 'route' => 'notifications.index', 'icon' => '🔔', 'label' => 'الإشعارات', 'badge' => $unreadNotifs],
                    ['id' => 'support', 'route' => 'support.index', 'icon' => '🎧', 'label' => 'الدعم', 'badge' => $openTickets],
                    ['id' => 'addresses', 'route' => 'addresses.index', 'icon' => '📒', 'label' => 'العناوين'],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙️', 'label' => 'الإعدادات'],
                ];

                $menu = match($portalType) {
                    'b2c' => $b2cMenu,
                    'admin' => $adminMenu,
                    default => $b2bMenu,
                };
            ?>

            <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(isset($item['d'])): ?>
                    <div class="sidebar-divider"></div>
                <?php elseif(isset($item['g'])): ?>
                    <div class="sidebar-group-label"><?php echo e($item['g']); ?></div>
                <?php else: ?>
                    <?php
                        $isActive = str_starts_with($currentRoute, $item['id']) || $currentRoute === $item['route'];
                    ?>
                    <a href="<?php echo e(route($item['route'])); ?>"
                       class="sidebar-item <?php echo e($isActive ? 'active' : ''); ?>"
                       <?php if($portalType === 'b2c' && $isActive): ?> style="background:rgba(13,148,136,0.13);color:#0D9488"
                       <?php elseif($portalType === 'admin' && $isActive): ?> style="background:rgba(124,58,237,0.13);color:#7C3AED"
                       <?php endif; ?>>
                        <span class="icon"><?php echo e($item['icon']); ?></span>
                        <span><?php echo e($item['label']); ?></span>
                        <?php if(isset($item['badge']) && $item['badge'] > 0): ?>
                            <span class="badge-count"><?php echo e($item['badge']); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit">🚪 تسجيل الخروج</button>
            </form>
        </div>
    </aside>

    
    <div class="main-area">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px">
                <?php if($portalType === 'admin'): ?>
                    <span style="font-size:14px">🛡️</span>
                    <span style="font-weight:600;color:var(--tx);font-size:14px">مدير النظام</span>
                <?php elseif($portalType === 'b2b'): ?>
                    <span style="font-size:14px">🏢</span>
                    <span style="font-weight:600;color:var(--tx);font-size:14px"><?php echo e(auth()->user()->account->name ?? 'شركة التقنية المتقدمة'); ?></span>
                <?php else: ?>
                    <span style="font-weight:600;color:var(--tx);font-size:15px"><?php echo $__env->yieldContent('page-title', ''); ?></span>
                <?php endif; ?>
            </div>
            <div class="topbar-user">
                <button class="topbar-bell" onclick="window.location='<?php echo e(route('notifications.index')); ?>'">
                    🔔
                    <?php if($unreadNotifs > 0): ?><span class="dot"></span><?php endif; ?>
                </button>
                <div style="display:flex;align-items:center;gap:10px">
                    <?php
                        $avatarStyle = match($portalType) {
                            'b2c' => 'background:linear-gradient(135deg,#0D9488,#065F56);color:#fff',
                            'admin' => 'background:linear-gradient(135deg,#7C3AED,#4C1D95);color:#fff',
                            default => '',
                        };
                    ?>
                    <div class="topbar-avatar" style="<?php echo e($avatarStyle); ?>">
                        <?php echo e(mb_substr(auth()->user()->name ?? 'م', 0, 1)); ?>

                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--tx)"><?php echo e(auth()->user()->name ?? 'المستخدم'); ?></div>
                        <?php if($portalType !== 'b2c'): ?>
                            <div style="font-size:11px;color:var(--td)"><?php echo e($portalType === 'admin' ? 'مدير النظام' : (auth()->user()->role_name ?? 'مدير')); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            
            <?php if(session('success')): ?>
                <div class="toast-container"><div class="toast toast-success">✅ <?php echo e(session('success')); ?></div></div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="toast-container"><div class="toast toast-danger">❌ <?php echo e(session('error')); ?></div></div>
            <?php endif; ?>
            <?php if(session('warning')): ?>
                <div class="toast-container"><div class="toast toast-warning">⚠️ <?php echo e(session('warning')); ?></div></div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
</div>

<script src="<?php echo e(asset('js/app.js')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/layouts/app.blade.php ENDPATH**/ ?>