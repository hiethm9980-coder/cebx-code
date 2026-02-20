<?php $__env->startSection('title', $portalType === 'b2c' ? 'شحناتي' : 'إدارة الشحنات'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">📦 <?php echo e($portalType === 'b2c' ? 'شحناتي' : 'إدارة الشحنات'); ?></h1>
    <div style="display:flex;gap:10px">
        <?php if($portalType === 'b2b'): ?>
            <a href="<?php echo e(route('shipments.export')); ?>" class="btn btn-s">📥 تصدير</a>
        <?php endif; ?>
        <a href="<?php echo e(route('shipments.create')); ?>" class="btn btn-pr">+ شحنة جديدة</a>
    </div>
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
    <form method="GET" action="<?php echo e(route('shipments.index')); ?>"
          style="display:grid;grid-template-columns:<?php echo e($portalType === 'b2b' ? '2fr 1fr 1fr 1fr 1fr auto' : '1fr auto'); ?>;gap:12px;align-items:end">
        <div>
            <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                   placeholder="بحث برقم التتبع<?php echo e($portalType === 'b2b' ? ' أو اسم المستلم' : ''); ?>..."
                   class="form-input" style="width:100%">
        </div>
        <?php if($portalType === 'b2b'): ?>
            <select name="status" class="form-input">
                <option value="">كل الحالات</option>
                <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>قيد الانتظار</option>
                <option value="processing" <?php echo e(request('status') === 'processing' ? 'selected' : ''); ?>>قيد المعالجة</option>
                <option value="in_transit" <?php echo e(request('status') === 'in_transit' ? 'selected' : ''); ?>>تم الشحن</option>
                <option value="delivered" <?php echo e(request('status') === 'delivered' ? 'selected' : ''); ?>>تم التسليم</option>
            </select>
            <select name="carrier" class="form-input">
                <option value="">كل الناقلين</option>
                <option value="aramex">أرامكس</option>
                <option value="smsa">سمسا</option>
                <option value="dhl">DHL</option>
                <option value="fedex">فيدكس</option>
            </select>
            <select name="source" class="form-input">
                <option value="">كل المصادر</option>
                <option value="direct">يدوي</option>
                <option value="order">طلب</option>
            </select>
            <input type="date" name="date" value="<?php echo e(request('date')); ?>" class="form-input">
        <?php else: ?>
            <select name="status" class="form-input" style="width:auto">
                <option value="">كل الحالات</option>
                <option value="pending">نشطة</option>
                <option value="delivered">مسلّمة</option>
                <option value="cancelled">ملغية</option>
            </select>
        <?php endif; ?>
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


<?php if($portalType === 'b2b'): ?>
    
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
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="checkbox" id="selectAll">
                <label for="selectAll" style="font-size:13px;color:var(--td)">تحديد الكل</label>
                <button class="btn btn-s" style="margin-right:12px" onclick="window.print()">🖨️ طباعة البوالص</button>
            </div>
            <span style="font-size:13px;color:var(--td)">إجمالي: <?php echo e($shipments->total()); ?> شحنة</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:30px"></th>
                    <th>رقم التتبع</th><th>المستلم</th><th>هاتف</th><th>الناقل</th>
                    <th>الخدمة</th><th>المدينة</th><th>COD</th><th>الوزن</th>
                    <th>الحالة</th><th>التاريخ</th><th></th>
                </tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $shipments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><input type="checkbox" name="selected[]" value="<?php echo e($shipment->id); ?>"></td>
                            <td><a href="<?php echo e(route('shipments.show', $shipment)); ?>" class="td-link td-mono"><?php echo e($shipment->reference_number); ?></a></td>
                            <td><?php echo e($shipment->recipient_name); ?></td>
                            <td style="direction:ltr;text-align:right"><?php echo e($shipment->recipient_phone); ?></td>
                            <td><span class="badge badge-in"><?php echo e($shipment->carrier_code ?? '—'); ?></span></td>
                            <td><?php echo e($shipment->service_name ?? $shipment->service_code ?? '—'); ?></td>
                            <td><?php echo e($shipment->recipient_city); ?></td>
                            <td style="font-family:monospace"><?php echo e($shipment->is_cod ? number_format($shipment->cod_amount) . ' ر.س' : '—'); ?></td>
                            <td><?php echo e($shipment->total_weight ?? '—'); ?> كغ</td>
                            <td><?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
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
<?php endif; ?></td>
                            <td><?php echo e($shipment->created_at->format('d/m')); ?></td>
                            <td><a href="<?php echo e(route('shipments.show', $shipment)); ?>" class="btn btn-s">👁️</a></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="12" class="empty-state">لا توجد شحنات</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px"><?php echo e($shipments->links()); ?></div>
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
<?php else: ?>
    
    <div style="display:flex;flex-direction:column;gap:14px">
        <?php $__empty_1 = true; $__currentLoopData = $shipments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e(route('shipments.show', $shipment)); ?>" style="text-decoration:none">
                <div class="entity-card" style="padding:20px 24px;cursor:pointer">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div style="display:flex;gap:16px;align-items:center">
                            <div style="width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;
                                <?php if($shipment->status === 'delivered'): ?> background:rgba(16,185,129,0.13)
                                <?php elseif(in_array($shipment->status, ['shipped','in_transit'])): ?> background:rgba(139,92,246,0.13)
                                <?php elseif($shipment->status === 'out_for_delivery'): ?> background:rgba(59,130,246,0.13)
                                <?php elseif($shipment->status === 'cancelled'): ?> background:rgba(239,68,68,0.13)
                                <?php else: ?> background:rgba(245,158,11,0.13) <?php endif; ?>">
                                <?php if($shipment->status === 'delivered'): ?> ✅
                                <?php elseif(in_array($shipment->status, ['shipped','in_transit'])): ?> 🚚
                                <?php elseif($shipment->status === 'out_for_delivery'): ?> 🏃
                                <?php elseif($shipment->status === 'cancelled'): ?> ❌
                                <?php else: ?> ⏳ <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-family:monospace;color:#0D9488;font-weight:700;font-size:15px"><?php echo e($shipment->reference_number); ?></div>
                                <div style="font-size:13px;color:var(--tx);margin-top:4px">إلى: <?php echo e($shipment->recipient_name); ?></div>
                                <div style="font-size:12px;color:var(--td);margin-top:2px">📍 <?php echo e($shipment->sender_city); ?> → <?php echo e($shipment->recipient_city); ?> • <?php echo e($shipment->carrier_code); ?></div>
                            </div>
                        </div>
                        <div style="text-align:left">
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
                            <div style="font-size:12px;color:var(--td);margin-top:8px"><?php echo e($shipment->created_at->format('d/m')); ?></div>
                            <div style="font-size:13px;font-family:monospace;color:var(--tx);margin-top:2px"><?php echo e(number_format($shipment->total_charge, 2)); ?> ر.س</div>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="empty-state">لا توجد شحنات</div>
        <?php endif; ?>
    </div>
    <div style="margin-top:20px"><?php echo e($shipments->links()); ?></div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/shipments/index.blade.php ENDPATH**/ ?>