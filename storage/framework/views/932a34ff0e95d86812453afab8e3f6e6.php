<?php $__env->startSection('title', 'الإشعارات'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">🔔 الإشعارات</h1>
    <?php if($unreadCount > 0): ?>
        <form method="POST" action="<?php echo e(route('notifications.readAll')); ?>" style="display:inline"><?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-s">✓ تحديد الكل كمقروء</button>
        </form>
    <?php endif; ?>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🔔','label' => 'الكل','value' => $notifications->total() ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🔔','label' => 'الكل','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notifications->total() ?? 0)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🔵','label' => 'غير مقروءة','value' => $unreadCount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🔵','label' => 'غير مقروءة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($unreadCount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '✅','label' => 'مقروءة','value' => $readCount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '✅','label' => 'مقروءة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($readCount)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:18px">
    <?php $__currentLoopData = ['' => 'الكل', 'unread' => 'غير مقروءة', 'shipment' => 'الشحنات', 'wallet' => 'المحفظة', 'system' => 'النظام']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="submit" name="filter" value="<?php echo e($val); ?>" class="btn <?php echo e(request('filter','') === $val ? 'btn-pr' : 'btn-s'); ?>" style="font-size:13px"><?php echo e($label); ?></button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</form>

<div style="display:flex;flex-direction:column;gap:8px">
    <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
            $icons = ['shipment' => '📦', 'wallet' => '💰', 'system' => '⚙️'];
        ?>
        <div class="card" style="opacity:<?php echo e($notif->read_at ? '0.7' : '1'); ?>">
            <div class="card-body" style="display:flex;align-items:center;gap:14px">
                <span style="font-size:24px"><?php echo e($icons[$notif->type] ?? '🔔'); ?></span>
                <div style="flex:1">
                    <div style="font-weight:<?php echo e($notif->read_at ? '500' : '700'); ?>;font-size:14px;color:var(--tx)"><?php echo e($notif->title); ?></div>
                    <div style="font-size:13px;color:var(--td);margin-top:4px"><?php echo e($notif->body); ?></div>
                    <div style="font-size:11px;color:var(--tm);margin-top:6px"><?php echo e($notif->created_at->diffForHumans()); ?></div>
                </div>
                <?php if(!$notif->read_at): ?>
                    <form method="POST" action="<?php echo e(route('notifications.read', $notif)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <button type="submit" class="btn btn-s" style="font-size:12px;padding:4px 12px">✓ قراءة</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state">لا توجد إشعارات</div>
    <?php endif; ?>
</div>

<?php if($notifications->hasPages()): ?>
    <div style="margin-top:14px"><?php echo e($notifications->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/notifications/index.blade.php ENDPATH**/ ?>