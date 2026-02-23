<?php $__env->startSection('title', 'الحاويات'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🚢 الحاويات</h1>
    <button class="btn btn-pr" data-modal-open="add-container">+ حاوية جديدة</button>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🚢','label' => 'إجمالي الحاويات','value' => $totalContainers ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🚢','label' => 'إجمالي الحاويات','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalContainers ?? 0)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🟢','label' => 'متاحة','value' => $availableCount ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🟢','label' => 'متاحة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($availableCount ?? 0)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🚚','label' => 'في الطريق','value' => $inTransitCount ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🚚','label' => 'في الطريق','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($inTransitCount ?? 0)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '📍','label' => 'في الميناء','value' => $atPortCount ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '📍','label' => 'في الميناء','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($atPortCount ?? 0)]); ?>
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
    <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
        <input type="text" placeholder="بحث برقم الحاوية..." class="form-input" style="flex:1;min-width:200px">
        <select class="form-input" style="width:auto">
            <option value="">جميع الأنواع</option>
            <option>20ft Standard</option>
            <option>40ft Standard</option>
            <option>40ft High Cube</option>
            <option>Reefer</option>
        </select>
        <select class="form-input" style="width:auto">
            <option value="">جميع الحالات</option>
            <option>متاحة</option>
            <option>محملة</option>
            <option>في الطريق</option>
            <option>في الميناء</option>
        </select>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>رقم الحاوية</th><th>النوع</th><th>الحجم</th><th>السفينة</th><th>ميناء المغادرة</th><th>ميناء الوصول</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $containers ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $container): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $stMap = ['available' => ['متاحة', 'badge-ac'], 'loaded' => ['محملة', 'badge-in'], 'in_transit' => ['في الطريق', 'badge-pp'], 'at_port' => ['في الميناء', 'badge-wn'], 'customs_hold' => ['احتجاز جمركي', 'badge-dg']];
                        $st = $stMap[$container->status] ?? ['—', 'badge-td'];
                    ?>
                    <tr>
                        <td class="td-mono" style="font-weight:600"><?php echo e($container->container_number); ?></td>
                        <td><?php echo e($container->type); ?></td>
                        <td><?php echo e($container->size); ?></td>
                        <td><?php echo e($container->vessel->name ?? '—'); ?></td>
                        <td><?php echo e($container->origin_port ?? '—'); ?></td>
                        <td><?php echo e($container->destination_port ?? '—'); ?></td>
                        <td><span class="badge <?php echo e($st[1]); ?>"><?php echo e($st[0]); ?></span></td>
                        <td><button class="btn btn-s" style="font-size:12px">تفاصيل</button></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="empty-state">لا توجد حاويات</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if(method_exists($containers ?? collect(), 'links')): ?>
        <div style="margin-top:14px"><?php echo e($containers->links()); ?></div>
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

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'add-container','title' => 'إضافة حاوية']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'add-container','title' => 'إضافة حاوية']); ?>
    <form method="POST" action="<?php echo e(route('containers.index')); ?>">
        <?php echo csrf_field(); ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div><label class="form-label">رقم الحاوية</label><input type="text" name="container_number" class="form-input" placeholder="ABCD1234567" required></div>
            <div><label class="form-label">النوع</label><select name="type" class="form-input"><option>Standard</option><option>High Cube</option><option>Reefer</option><option>Open Top</option><option>Flat Rack</option></select></div>
            <div><label class="form-label">الحجم</label><select name="size" class="form-input"><option>20ft</option><option>40ft</option><option>45ft</option></select></div>
            <div><label class="form-label">السفينة</label><select name="vessel_id" class="form-input"><option value="">— اختر السفينة —</option></select></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">إضافة</button>
        </div>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/containers/index.blade.php ENDPATH**/ ?>