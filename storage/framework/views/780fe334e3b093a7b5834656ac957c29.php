<?php $__env->startSection('title', 'دفتر العناوين'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">📒 دفتر العناوين</h1>
    <button class="btn btn-pr" data-modal-open="add-address" <?php if($portalType === 'b2c'): ?> style="background:#0D9488" <?php endif; ?>>+ عنوان جديد</button>
</div>

<div class="grid-2">
    <?php $__empty_1 = true; $__currentLoopData = $addresses ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $address): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                <div style="display:flex;gap:12px;align-items:center">
                    <div style="width:44px;height:44px;border-radius:12px;background:<?php echo e($address->is_default_sender ? 'rgba(13,148,136,0.13)' : 'var(--sf)'); ?>;display:flex;align-items:center;justify-content:center;font-size:20px">
                        <?php echo e($address->type === 'sender' ? '📤' : ($address->type === 'recipient' ? '📥' : '📍')); ?>

                    </div>
                    <div>
                        <div style="font-weight:600;color:var(--tx)"><?php echo e($address->label ?? $address->contact_name); ?></div>
                        <div style="font-size:12px;color:var(--td)"><?php echo e(ucfirst($address->type)); ?></div>
                    </div>
                </div>
                <?php if($address->is_default_sender): ?>
                    <span class="badge badge-ac">افتراضي</span>
                <?php endif; ?>
            </div>
            <div style="font-size:13px;color:var(--tm);line-height:2;margin-bottom:12px">
                <?php echo e($address->contact_name); ?><br>
                📞 <?php echo e($address->phone); ?><br>
                📍 <?php echo e($address->city); ?>، <?php echo e($address->address_line_1); ?>

            </div>
            <div style="display:flex;gap:8px">
                <?php if(!$address->is_default_sender): ?>
                    <form method="POST" action="<?php echo e(route('addresses.default', $address)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <button type="submit" class="btn btn-s">⭐ تعيين افتراضي</button>
                    </form>
                <?php endif; ?>
                <button class="btn btn-s">✏️ تعديل</button>
                <form method="POST" action="<?php echo e(route('addresses.destroy', $address)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-dg btn-sm" onclick="return confirm('حذف العنوان؟')">🗑️</button>
                </form>
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
        <div class="empty-state" style="grid-column:1/3">لا توجد عناوين محفوظة</div>
    <?php endif; ?>
</div>

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'add-address','title' => 'إضافة عنوان جديد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'add-address','title' => 'إضافة عنوان جديد']); ?>
    <form method="POST" action="<?php echo e(route('addresses.store')); ?>">
        <?php echo csrf_field(); ?>
        <div style="margin-bottom:16px"><label class="form-label">العنوان المحفوظ</label><input type="text" name="label" placeholder="مثال: المنزل، العمل..." class="form-input"></div>
        <div style="margin-bottom:16px"><label class="form-label">الاسم</label><input type="text" name="contact_name" placeholder="الاسم الكامل" class="form-input" required></div>
        <div style="margin-bottom:16px"><label class="form-label">الهاتف</label><input type="text" name="phone" placeholder="05xxxxxxxx" class="form-input" required></div>
        <div class="grid-2">
            <div style="margin-bottom:16px">
                <label class="form-label">الدولة</label>
                <select name="country" class="form-input">
                    <option value="SA">🇸🇦 السعودية</option><option value="AE">🇦🇪 الإمارات</option><option value="KW">🇰🇼 الكويت</option>
                </select>
            </div>
            <div style="margin-bottom:16px"><label class="form-label">المدينة</label><input type="text" name="city" placeholder="المدينة" class="form-input" required></div>
        </div>
        <div style="margin-bottom:16px"><label class="form-label">العنوان التفصيلي</label><input type="text" name="address_line_1" placeholder="الحي، الشارع، رقم المبنى" class="form-input" required></div>
        <label style="display:flex;align-items:center;gap:8px;color:var(--tm);font-size:13px;cursor:pointer;margin-bottom:16px">
            <input type="checkbox" name="is_default_sender"> تعيين كعنوان افتراضي
        </label>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr" <?php if($portalType === 'b2c'): ?> style="background:#0D9488" <?php endif; ?>>حفظ العنوان</button>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/addresses/index.blade.php ENDPATH**/ ?>