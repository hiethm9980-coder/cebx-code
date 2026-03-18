<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'بوابة إدارة الشحن'); ?> — CBEX Shipping Gateway</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>">

    
    <?php echo $__env->make('components.pwa-meta', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>
<div class="app-layout">
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="<?php echo e(asset('images/logo-sidebar.png')); ?>" alt="CBEX" class="sidebar-logo-img">
            <span class="sidebar-title">CBEX Gateway</span>
        </div>
        <nav class="sidebar-nav">
            <?php
                $currentRoute = Route::currentRouteName() ?? '';
                $unreadNotifs = \App\Models\Notification::where('read_at', null)->count();
                $openTickets = \App\Models\SupportTicket::where('status', 'open')->count();
                $processingShipments = \App\Models\Shipment::whereIn('status', ['payment_pending', 'purchased', 'picked_up', 'in_transit', 'out_for_delivery'])->count();

                // Sidebar route names must exist in routes/web.php (auth + tenant middleware group)
                $menu = [
                    ['d' => true, 'label' => 'الرئيسية'],
                    ['id' => 'dashboard', 'route' => 'dashboard', 'icon' => '🏠', 'label' => 'لوحة التحكم'],
                    ['id' => 'shipments', 'route' => 'shipments.index', 'icon' => '📦', 'label' => 'الشحنات', 'badge' => $processingShipments],
                    ['id' => 'orders', 'route' => 'orders.index', 'icon' => '🛒', 'label' => 'الطلبات'],
                    ['id' => 'stores', 'route' => 'stores.index', 'icon' => '🏪', 'label' => 'المتاجر'],
                    ['id' => 'tracking', 'route' => 'tracking.index', 'icon' => '🚚', 'label' => 'التتبع'],
                    ['id' => 'pricing', 'route' => 'pricing.index', 'icon' => '🏷', 'label' => 'التسعير'],
                    ['d' => true, 'label' => 'المالية'],
                    ['id' => 'wallet', 'route' => 'wallet.index', 'icon' => '💰', 'label' => 'المحفظة'],
                    ['id' => 'financial', 'route' => 'financial.index', 'icon' => '📊', 'label' => 'المالية'],
                    ['d' => true, 'label' => 'الإدارة'],
                    ['id' => 'users', 'route' => 'users.index', 'icon' => '👥', 'label' => 'المستخدمين'],
                    ['id' => 'roles', 'route' => 'roles.index', 'icon' => '🛡', 'label' => 'الأدوار'],
                    ['id' => 'invitations', 'route' => 'invitations.index', 'icon' => '📧', 'label' => 'الدعوات'],
                    ['id' => 'organizations', 'route' => 'organizations.index', 'icon' => '🏢', 'label' => 'المنظمات'],
                    ['d' => true, 'label' => 'النظام'],
                    ['id' => 'notifications', 'route' => 'notifications.index', 'icon' => '🔔', 'label' => 'الإشعارات', 'badge' => $unreadNotifs],
                    ['id' => 'reports', 'route' => 'reports.index', 'icon' => '📈', 'label' => 'التقارير'],
                    ['id' => 'audit', 'route' => 'audit.index', 'icon' => '📋', 'label' => 'التدقيق'],
                    ['id' => 'kyc', 'route' => 'kyc.index', 'icon' => '✅', 'label' => 'KYC'],
                    ['id' => 'dg', 'route' => 'dg.index', 'icon' => '⚠', 'label' => 'DG'],
                    ['id' => 'support', 'route' => 'support.index', 'icon' => '🎧', 'label' => 'الدعم', 'badge' => $openTickets],
                    ['id' => 'addresses', 'route' => 'addresses.index', 'icon' => '📍', 'label' => 'العناوين'],
                    ['id' => 'settings', 'route' => 'settings.index', 'icon' => '⚙', 'label' => 'الإعدادات'],
                    ['id' => 'admin', 'route' => 'admin.index', 'icon' => '🔑', 'label' => 'الإدارة'],
                    ['d' => true, 'label' => 'Phase 2'],
                    ['id' => 'containers', 'route' => 'containers.index', 'icon' => '📦', 'label' => 'الحاويات'],
                    ['id' => 'customs', 'route' => 'customs.index', 'icon' => '📄', 'label' => 'الجمارك'],
                    ['id' => 'drivers', 'route' => 'drivers.index', 'icon' => '🚗', 'label' => 'السائقين'],
                    ['id' => 'claims', 'route' => 'claims.index', 'icon' => '⚡', 'label' => 'المطالبات'],
                    ['id' => 'risk', 'route' => 'risk.index', 'icon' => '🛡', 'label' => 'المخاطر'],
                    ['id' => 'vessels', 'route' => 'vessels.index', 'icon' => '⚓', 'label' => 'السفن'],
                    ['id' => 'schedules', 'route' => 'schedules.index', 'icon' => '📅', 'label' => 'الجداول'],
                    ['id' => 'branches', 'route' => 'branches.index', 'icon' => '🏛', 'label' => 'الفروع'],
                    ['id' => 'companies', 'route' => 'companies.index', 'icon' => '🌐', 'label' => 'الشركات'],
                    ['id' => 'hscodes', 'route' => 'hscodes.index', 'icon' => '#️⃣', 'label' => 'HS أكواد'],
                ];
            ?>

            <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(isset($item['d'])): ?>
                    <div class="sidebar-divider"><?php echo e($item['label']); ?></div>
                <?php else: ?>
                    <?php
                        $isActive = str_starts_with($currentRoute, $item['id']);
                        // Use web route only: relative path so session/cookie same-origin (avoid redirect to login)
                        $url = \Illuminate\Support\Facades\Route::has($item['route'])
                            ? (\Illuminate\Support\Str::startsWith($item['route'] ?? '', 'api.') ? '#' : route($item['route'], [], false))
                            : '#';
                    ?>
                    <a href="<?php echo e($url); ?>"
                       class="sidebar-item <?php echo e($isActive ? 'active' : ''); ?>"
                       <?php if($url === '#'): ?> title="<?php echo e(__('Route not registered: ')); ?><?php echo e($item['route']); ?>" <?php endif; ?>>
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
            <form action="<?php echo e(route('logout')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit">🚪 <span>تسجيل الخروج</span></button>
            </form>
        </div>
    </aside>

    
    <div class="main-area">
        <header class="topbar">
            <div style="color: var(--tm); font-size: 11px;">
                مرحباً، <?php echo e(auth()->user()->name ?? 'مستخدم'); ?>

            </div>
            <div class="topbar-user">
                <button class="topbar-bell" onclick="window.location='/notifications'">
                    🔔
                    <?php if(($unreadNotifs ?? 0) > 0): ?> <span class="dot"></span> <?php endif; ?>
                </button>
                <div class="topbar-avatar"><?php echo e(mb_substr(auth()->user()->name ?? 'م', 0, 1)); ?></div>
            </div>
        </header>

        <main class="content">
            <?php if(session('success')): ?>
                <?php if (isset($component)) { $__componentOriginal7cfab914afdd05940201ca0b2cbc009b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast','data' => ['type' => 'success','message' => session('success')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'success','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('success'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $attributes = $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $component = $__componentOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <?php if (isset($component)) { $__componentOriginal7cfab914afdd05940201ca0b2cbc009b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast','data' => ['type' => 'error','message' => session('error')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('error'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $attributes = $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__attributesOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b)): ?>
<?php $component = $__componentOriginal7cfab914afdd05940201ca0b2cbc009b; ?>
<?php unset($__componentOriginal7cfab914afdd05940201ca0b2cbc009b); ?>
<?php endif; ?>
            <?php endif; ?>
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>
</div>


<script src="<?php echo e(asset('js/app.js')); ?>"></script>

<script src="<?php echo e(asset('js/pwa.js')); ?>"></script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/layouts/app.blade.php ENDPATH**/ ?>