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
    
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">SG</div>
            <span class="sidebar-title">Shipping Gateway</span>
        </div>
        <nav class="sidebar-nav">
            <?php
                $currentRoute = Route::currentRouteName() ?? '';
                $unreadNotifs = \App\Models\Notification::where('read_at', null)->count();
                $openTickets = \App\Models\SupportTicket::where('status', 'open')->count();
                $processingShipments = \App\Models\Shipment::where('status', 'processing')->count();

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
                    ?>
                    <a href="<?php echo e(route($item['route'])); ?>"
                       class="sidebar-item <?php echo e($isActive ? 'active' : ''); ?>">
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
                مرحباً، <?php echo e(auth()->user()->name ?? 'المستخدم'); ?> 👋
            </div>
            <div class="topbar-user">
                <a href="<?php echo e(route('notifications.index')); ?>" class="topbar-bell">
                    🔔
                    <?php if($unreadNotifs > 0): ?>
                        <span class="dot"></span>
                    <?php endif; ?>
                </a>
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="topbar-avatar"><?php echo e(mb_substr(auth()->user()->name ?? 'م', 0, 1)); ?></div>
                    <span style="font-size:11px;font-weight:600"><?php echo e(auth()->user()->name ?? 'المستخدم'); ?></span>
                </div>
            </div>
        </header>

        <div class="content fade-in">
            
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast','data' => ['type' => 'danger','message' => session('error')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'danger','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('error'))]); ?>
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
            <?php if(session('warning')): ?>
                <?php if (isset($component)) { $__componentOriginal7cfab914afdd05940201ca0b2cbc009b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7cfab914afdd05940201ca0b2cbc009b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast','data' => ['type' => 'warning','message' => session('warning')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'warning','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('warning'))]); ?>
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
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss toasts
    document.querySelectorAll('.toast').forEach(function(t) {
        setTimeout(function() { t.style.opacity = '0'; setTimeout(function() { t.remove(); }, 300); }, 3000);
    });
    // Modal close
    document.querySelectorAll('[data-modal-close]').forEach(function(b) {
        b.addEventListener('click', function() {
            var m = this.closest('.modal-bg');
            if (m) m.remove();
        });
    });
    document.querySelectorAll('[data-modal-open]').forEach(function(b) {
        b.addEventListener('click', function() {
            var t = this.dataset.modalOpen;
            var m = document.getElementById(t);
            if (m) m.style.display = 'flex';
        });
    });
    document.querySelectorAll('.modal-bg').forEach(function(m) {
        m.addEventListener('click', function(e) {
            if (e.target === m) m.style.display = 'none';
        });
    });
    // Confirm deletes
    document.querySelectorAll('[data-confirm]').forEach(function(f) {
        f.addEventListener('submit', function(e) {
            if (!confirm(f.dataset.confirm || 'هل أنت متأكد؟')) e.preventDefault();
        });
    });
});
</script>
<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/layouts/app.blade.php ENDPATH**/ ?>