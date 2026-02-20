<?php $__env->startSection('title', 'إدارة الطلبات'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🛒 إدارة الطلبات</h1>
    <button class="btn btn-pr" onclick="syncOrders()">🔄 مزامنة الطلبات</button>
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
    <form method="GET" action="<?php echo e(route('orders.index')); ?>" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end">
        <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="بحث برقم الطلب أو اسم العميل..." class="form-input">
        <select name="status" class="form-input">
            <option value="">كل الحالات</option>
            <option value="pending">جديد</option>
            <option value="ready">جاهز للشحن</option>
            <option value="shipped">تم الشحن</option>
        </select>
        <select name="store_id" class="form-input">
            <option value="">كل المتاجر</option>
            <?php $__currentLoopData = $stores ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($store->id); ?>"><?php echo e($store->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="btn btn-pr" style="height:42px">بحث</button>
    </form>
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
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>رقم الطلب</th><th>المتجر</th><th>العميل</th><th>المنتجات</th>
                <th>المبلغ</th><th>الحالة</th><th>التاريخ</th><th></th>
            </tr></thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $platformIcons = ['salla' => '🟣', 'zid' => '🔵', 'shopify' => '🟢', 'woocommerce' => '🟠'];
                        $icon = $platformIcons[$order->source] ?? '📦';
                    ?>
                    <tr>
                        <td class="td-mono" style="font-weight:600"><?php echo e($order->external_order_number); ?></td>
                        <td><?php echo e($icon); ?> <?php echo e($order->store?->name ?? $order->source); ?></td>
                        <td><?php echo e($order->customer_name); ?></td>
                        <td><?php echo e($order->items_count ?? '—'); ?> منتج</td>
                        <td style="font-family:monospace"><?php echo e(number_format($order->total_amount ?? 0)); ?> ر.س</td>
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
                        <td><?php echo e($order->created_at->format('d/m')); ?></td>
                        <td>
                            <?php if(in_array($order->status, ['pending', 'ready'])): ?>
                                <form method="POST" action="<?php echo e(route('orders.ship', $order)); ?>" style="display:inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-pr btn-sm">🚚 شحن</button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo e(route('shipments.show', $order->shipment_id ?? '#')); ?>" class="btn btn-s">👁️</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="empty-state">لا توجد طلبات</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if(isset($orders) && method_exists($orders, 'links')): ?>
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