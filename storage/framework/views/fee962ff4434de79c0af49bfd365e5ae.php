<?php $__env->startSection('title', 'التتبع'); ?>

<?php $__env->startSection('content'); ?>
<div style="text-align:center;padding:40px 0 32px">
    <div style="font-size:48px;margin-bottom:16px">🔍</div>
    <h1 style="font-size:28px;font-weight:700;color:var(--tx);margin:0 0 8px">تتبع شحنتك</h1>
    <p style="color:var(--td);font-size:15px">أدخل رقم التتبع لمعرفة حالة شحنتك</p>
</div>

<div style="max-width:600px;margin:0 auto 40px">
    <form action="<?php echo e(route('tracking.index')); ?>" method="GET" style="display:flex;gap:12px">
        <div style="flex:1">
            <input type="text" name="tracking_number" value="<?php echo e(request('tracking_number')); ?>"
                   placeholder="أدخل رقم التتبع... مثال: TRK-8891"
                   class="form-input form-input-lg" style="width:100%;height:56px;font-size:18px">
        </div>
        <button type="submit" class="btn btn-pr" style="height:56px;padding:0 32px;border-radius:14px;font-size:16px;background:#0D9488">تتبع</button>
    </form>
</div>

<?php if(isset($trackedShipment)): ?>
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
        <div style="max-width:700px;margin:0 auto">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
                <div>
                    <div style="font-family:monospace;color:#0D9488;font-weight:700;font-size:20px"><?php echo e($trackedShipment->reference_number); ?></div>
                    <div style="font-size:13px;color:var(--td);margin-top:4px">
                        <?php echo e($trackedShipment->carrier_code); ?> • <?php echo e($trackedShipment->service_name ?? ''); ?> •
                        <?php echo e($trackedShipment->sender_city); ?> → <?php echo e($trackedShipment->recipient_city); ?>

                    </div>
                </div>
                <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $trackedShipment->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trackedShipment->status)]); ?>
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

            
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
                <?php $__currentLoopData = [
                    ['الوزن', ($trackedShipment->total_weight ?? '—') . ' كغ'],
                    ['القطع', $trackedShipment->parcels_count ?? 1],
                    ['COD', $trackedShipment->is_cod ? number_format($trackedShipment->cod_amount) . ' ر.س' : '—'],
                    ['الوصول المتوقع', $trackedShipment->estimated_delivery_at ? $trackedShipment->estimated_delivery_at->format('d/m') : '—'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $info): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="padding:14px;background:var(--sf);border-radius:10px;text-align:center">
                        <div style="font-size:11px;color:var(--td)"><?php echo e($info[0]); ?></div>
                        <div style="font-weight:600;color:var(--tx);margin-top:4px"><?php echo e($info[1]); ?></div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php if (isset($component)) { $__componentOriginal93f2afea2d7941ca7799292711b7f46f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f2afea2d7941ca7799292711b7f46f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.timeline','data' => ['items' => $trackingHistory ?? [],'teal' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('timeline'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trackingHistory ?? []),'teal' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f2afea2d7941ca7799292711b7f46f)): ?>
<?php $attributes = $__attributesOriginal93f2afea2d7941ca7799292711b7f46f; ?>
<?php unset($__attributesOriginal93f2afea2d7941ca7799292711b7f46f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f2afea2d7941ca7799292711b7f46f)): ?>
<?php $component = $__componentOriginal93f2afea2d7941ca7799292711b7f46f; ?>
<?php unset($__componentOriginal93f2afea2d7941ca7799292711b7f46f); ?>
<?php endif; ?>

            <a href="<?php echo e(route('shipments.show', $trackedShipment)); ?>" class="btn btn-pr" style="width:100%;text-align:center;margin-top:16px;background:#0D9488;display:block">عرض التفاصيل الكاملة</a>
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
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📦 شحناتي النشطة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📦 شحناتي النشطة']); ?>
    <?php $__empty_1 = true; $__currentLoopData = $activeShipments ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $shipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;<?php echo e(!$loop->last ? 'border-bottom:1px solid var(--bd)' : ''); ?>;cursor:pointer"
             onclick="window.location='<?php echo e(route('tracking.index', ['tracking_number' => $shipment->tracking_number])); ?>'">
            <div>
                <span style="font-family:monospace;color:#0D9488;font-weight:600"><?php echo e($shipment->reference_number); ?></span>
                <span style="color:var(--td);font-size:13px;margin-right:12px"><?php echo e($shipment->sender_city); ?> → <?php echo e($shipment->recipient_city); ?></span>
            </div>
            <?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $shipment->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->status)]); ?>
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
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty-state">لا توجد شحنات نشطة</div>
    <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/tracking/index.blade.php ENDPATH**/ ?>