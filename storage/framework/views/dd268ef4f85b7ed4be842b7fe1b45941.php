<?php $__env->startSection('title', 'الطلبات'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'الطلبات','subtitle' => $orders->total() . ' طلب']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الطلبات','subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($orders->total() . ' طلب')]); ?>
    <button class="btn btn-pr" data-modal-open="create-order">+ طلب يدوي</button>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<div class="table-wrap">
    <table>
        <thead><tr><th>الرقم</th><th>العميل</th><th>المبلغ</th><th>المنتجات</th><th>المصدر</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr></thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="td-link"><?php echo e($o->order_number); ?></td>
                    <td><?php echo e($o->customer_name); ?></td>
                    <td style="font-family:monospace"><?php echo e(number_format($o->total_amount, 2)); ?> ر.س</td>
                    <td><?php echo e($o->items_count ?? $o->items()->count()); ?></td>
                    <td><span class="badge badge-in"><?php echo e($o->store?->platform ?? 'يدوي'); ?></span></td>
                    <td><?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $o->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($o->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?></td>
                    <td><?php echo e($o->created_at->format('Y-m-d')); ?></td>
                    <td class="td-actions">
                        <?php if($o->status === 'pending'): ?>
                            <form action="<?php echo e(route('orders.ship', $o)); ?>" method="POST"><?php echo csrf_field(); ?> <button class="btn btn-ac">🚚 شحن</button></form>
                            <form action="<?php echo e(route('orders.cancel', $o)); ?>" method="POST" data-confirm="إلغاء؟"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?> <button class="btn btn-dg">✕</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="empty-state">لا توجد طلبات</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div style="margin-top:14px"><?php echo e($orders->links()); ?></div>

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'create-order','title' => 'طلب يدوي جديد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'create-order','title' => 'طلب يدوي جديد']); ?>
    <form method="POST" action="<?php echo e(route('orders.store')); ?>"><?php echo csrf_field(); ?>
        <div class="form-grid">
            <div class="form-group"><label class="form-label">اسم العميل *</label><input name="customer_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">البريد</label><input name="customer_email" type="email" class="form-control"></div>
            <div class="form-group"><label class="form-label">المبلغ *</label><input name="total_amount" type="number" step="0.01" class="form-control" required></div>
            <div class="form-group"><label class="form-label">العنوان</label><input name="shipping_address" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:10px">إنشاء</button>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\resources\views/pages/orders/index.blade.php ENDPATH**/ ?>