<?php $__env->startSection('title', 'الشحنات'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => 'الشحنات','subtitle' => $shipments->total() . ' شحنة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الشحنات','subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipments->total() . ' شحنة')]); ?>
    <button class="btn btn-pr" data-modal-open="create-shipment">+ إنشاء شحنة</button>
    <a href="<?php echo e(route('shipments.export')); ?>" class="btn btn-s">📥 تصدير</a>
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


<div class="tabs">
    <a href="<?php echo e(route('shipments.index')); ?>" class="tab-btn <?php echo e(!request('status') ? 'active' : ''); ?>">الكل <span class="count"><?php echo e($totalCount); ?></span></a>
    <a href="<?php echo e(route('shipments.index', ['status' => 'processing'])); ?>" class="tab-btn <?php echo e(request('status') === 'processing' ? 'active' : ''); ?>">معالجة</a>
    <a href="<?php echo e(route('shipments.index', ['status' => 'shipped'])); ?>" class="tab-btn <?php echo e(request('status') === 'shipped' ? 'active' : ''); ?>">مشحون</a>
    <a href="<?php echo e(route('shipments.index', ['status' => 'delivered'])); ?>" class="tab-btn <?php echo e(request('status') === 'delivered' ? 'active' : ''); ?>">مُسلّم</a>
    <a href="<?php echo e(route('shipments.index', ['status' => 'cancelled'])); ?>" class="tab-btn <?php echo e(request('status') === 'cancelled' ? 'active' : ''); ?>">ملغي</a>
</div>


<form method="GET" style="margin-bottom:14px">
    <input type="text" name="search" class="form-control" style="max-width:400px" placeholder="بحث بالرقم أو التتبع أو العميل..." value="<?php echo e(request('search')); ?>">
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>الرقم</th><th>التتبع</th><th>الناقل</th><th>الحالة</th><th>العميل</th><th>المسار</th><th>التكلفة</th><th>إجراء</th></tr></thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $shipments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><a href="<?php echo e(route('shipments.show', $s)); ?>" class="td-link"><?php echo e($s->tracking_number); ?></a></td>
                    <td class="td-mono"><?php echo e($s->carrier_tracking_number); ?></td>
                    <td><span class="badge badge-in"><?php echo e($s->carrier_code); ?></span></td>
                    <td><?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['status' => $s->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($s->status)]); ?>
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
                    <td><?php echo e($s->recipient_name); ?></td>
                    <td><?php echo e($s->origin_city); ?> → <?php echo e($s->destination_city); ?></td>
                    <td style="font-family:monospace"><?php echo e(number_format($s->total_cost, 2)); ?> ر.س</td>
                    <td class="td-actions">
                        <a href="<?php echo e(route('shipments.show', $s)); ?>" class="btn btn-ghost">👁</a>
                        <?php if(!in_array($s->status, ['cancelled', 'delivered'])): ?>
                            <form action="<?php echo e(route('shipments.cancel', $s)); ?>" method="POST" data-confirm="هل أنت متأكد من إلغاء الشحنة؟">
                                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                <button class="btn btn-ghost" style="color:var(--dg)">✕</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="8" class="empty-state">لا توجد شحنات</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<div style="margin-top:14px"><?php echo e($shipments->links()); ?></div>


<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'create-shipment','title' => 'إنشاء شحنة جديدة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'create-shipment','title' => 'إنشاء شحنة جديدة']); ?>
    <form method="POST" action="<?php echo e(route('shipments.store')); ?>">
        <?php echo csrf_field(); ?>
        <div class="form-grid">
            <div class="form-group"><label class="form-label">اسم المستلم *</label><input name="recipient_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">الناقل</label>
                <select name="carrier_code" class="form-control">
                    <?php $__currentLoopData = ['DHL','Aramex','SMSA','FedEx','UPS','SPL']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($c); ?>"><?php echo e($c); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">مدينة المرسل *</label><input name="origin_city" class="form-control" required></div>
            <div class="form-group"><label class="form-label">مدينة المستلم *</label><input name="destination_city" class="form-control" required></div>
            <div class="form-group"><label class="form-label">الوزن (كغ)</label><input name="weight" type="number" step="0.1" class="form-control"></div>
            <div class="form-group"><label class="form-label">التكلفة</label><input name="total_cost" type="number" step="0.01" class="form-control"></div>
            <div class="form-group"><label class="form-label">الخدمة</label>
                <select name="service_type" class="form-control">
                    <option value="express">Express</option><option value="standard">Standard</option><option value="economy">Economy</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">الأبعاد</label><input name="dimensions" class="form-control" placeholder="30×20×15 سم"></div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:12px">إنشاء</button>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/pages/shipments/index.blade.php ENDPATH**/ ?>