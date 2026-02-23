<?php $__env->startSection('title', 'إدارة الطلبات'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">🛒 الطلبات</h1>
    <button type="button" class="btn btn-pr" data-modal-open="syncOrders">🔄 مزامنة الطلبات</button>
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
    <form method="GET" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
        <?php $__currentLoopData = ['' => 'الكل', 'new' => 'جديد', 'processing' => 'قيد المعالجة', 'shipped' => 'تم الشحن', 'delivered' => 'مسلّم']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="submit" name="status" value="<?php echo e($val); ?>" class="btn <?php echo e(request('status','') === $val ? 'btn-pr' : 'btn-s'); ?>" style="font-size:13px"><?php echo e($label); ?></button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div style="flex:1"></div>
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="بحث..." class="form-input" style="width:200px">
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>رقم الطلب</th><th>المتجر</th><th>المنتجات</th><th>المبلغ</th><th>الحالة</th><th>التاريخ</th><th></th>
            </tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="td-mono" style="font-weight:600"><?php echo e($order->order_number); ?></td>
                        <td><?php echo e($order->store->name ?? '—'); ?></td>
                        <td><?php echo e($order->items_count); ?> منتج</td>
                        <td style="font-weight:600">SAR <?php echo e(number_format($order->total_amount, 2)); ?></td>
                        <td><?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $order->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($order->status)]); ?>
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
                        <td style="font-size:12px;color:var(--tm)"><?php echo e($order->created_at->format('Y-m-d')); ?></td>
                        <td>
                            <?php if($order->status === 'new' || $order->status === 'processing'): ?>
                                <form method="POST" action="<?php echo e(route('orders.ship', $order)); ?>" style="display:inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-pr" style="font-size:12px;padding:5px 14px">شحن</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="empty-state">لا توجد طلبات</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if($orders->hasPages()): ?>
        <div style="margin-top:14px"><?php echo e($orders->links()); ?></div>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/orders/index.blade.php ENDPATH**/ ?>