<?php $__env->startSection('title', 'إدارة المتاجر'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">🏪 المتاجر</h1>
    <button type="button" class="btn btn-pr" data-modal-open="addStore">+ ربط متجر</button>
</div>

<?php
    $platformIcons = ['salla' => '🟣', 'zid' => '🔵', 'shopify' => '🟢', 'woocommerce' => '🟠'];
    $platformNames = ['salla' => 'سلة', 'zid' => 'زد', 'shopify' => 'شوبيفاي', 'woocommerce' => 'ووكومرس'];
?>

<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px">
    <?php $__empty_1 = true; $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div style="display:flex;gap:14px;align-items:center">
                    <div style="width:48px;height:48px;border-radius:12px;background:var(--sf);display:flex;align-items:center;justify-content:center;font-size:24px">
                        <?php echo e($platformIcons[$store->platform] ?? '🏪'); ?>

                    </div>
                    <div>
                        <div style="font-weight:700;font-size:15px;color:var(--tx)"><?php echo e($store->name); ?></div>
                        <div style="font-size:12px;color:var(--tm);margin-top:2px"><?php echo e($platformNames[$store->platform] ?? $store->platform); ?> • <?php echo e($store->orders_count); ?> طلب</div>
                    </div>
                </div>
                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $store->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($store->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
            </div>
            <div style="margin-top:14px;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;color:var(--tm)">
                    آخر مزامنة: <?php echo e($store->last_sync_at ? $store->last_sync_at->diffForHumans() : 'لم تتم'); ?>

                </span>
                <div style="display:flex;gap:8px">
                    <form method="POST" action="<?php echo e(route('stores.sync', $store)); ?>" style="display:inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-s" style="font-size:12px;padding:5px 14px">🔄 مزامنة</button>
                    </form>
                    <?php if($store->status === 'connected'): ?>
                        <form method="POST" action="<?php echo e(route('stores.disconnect', $store)); ?>" style="display:inline">
                            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-dg" style="font-size:12px;padding:5px 14px">فصل</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state" style="grid-column:1/-1;padding:60px">لا توجد متاجر مربوطة — اربط متجرك الآن</div>
    <?php endif; ?>
</div>


<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'addStore','title' => 'ربط متجر جديد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'addStore','title' => 'ربط متجر جديد']); ?>
    <form method="POST" action="<?php echo e(route('stores.store')); ?>">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom:16px">
            <label class="form-label">اسم المتجر</label>
            <input type="text" name="name" class="form-input" required placeholder="متجري">
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">المنصة</label>
            <select name="platform" class="form-input" required>
                <option value="salla">سلة</option><option value="zid">زد</option>
                <option value="shopify">شوبيفاي</option><option value="woocommerce">ووكومرس</option>
            </select>
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">رابط المتجر</label>
            <input type="url" name="store_url" class="form-input" placeholder="https://mystore.com">
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">API Key</label>
            <input type="text" name="api_key" class="form-input" placeholder="sk_live_...">
        </div>
        <button type="submit" class="btn btn-pr" style="width:100%">ربط المتجر</button>
    </form>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/stores/index.blade.php ENDPATH**/ ?>