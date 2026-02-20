<?php $__env->startSection('title', 'الإعدادات'); ?>

<?php $__env->startSection('content'); ?>
<h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0 0 24px">⚙️ الإعدادات</h1>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
    
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🏢 معلومات المنظمة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🏢 معلومات المنظمة']); ?>
        <form method="POST" action="<?php echo e(route('settings.update')); ?>">
            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
            <?php $__currentLoopData = [
                ['name', 'اسم الشركة', $account->name ?? ''],
                ['cr_number', 'السجل التجاري', $account->cr_number ?? ''],
                ['vat_number', 'الرقم الضريبي', $account->vat_number ?? ''],
                ['email', 'البريد الإلكتروني', $account->email ?? ''],
                ['phone', 'رقم الهاتف', $account->phone ?? ''],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$field, $label, $val]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="margin-bottom:14px">
                    <label class="form-label"><?php echo e($label); ?></label>
                    <input type="<?php echo e($field === 'email' ? 'email' : 'text'); ?>" name="<?php echo e($field); ?>" class="form-input" value="<?php echo e($val); ?>">
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <button type="submit" class="btn btn-pr">حفظ التعديلات</button>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '🔑 مفاتيح API']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '🔑 مفاتيح API']); ?>
        <div style="background:var(--sf);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                <span style="font-size:13px;font-weight:600">Production Key</span>
                <span style="color:var(--ac);font-size:12px">● نشط</span>
            </div>
            <code style="font-size:12px;color:var(--td);background:var(--bd);padding:4px 8px;border-radius:6px">sk_live_****...a8f2</code>
        </div>
        <div style="background:var(--sf);border-radius:10px;padding:14px;margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                <span style="font-size:13px;font-weight:600">Test Key</span>
                <span style="color:var(--wn);font-size:12px">● اختبار</span>
            </div>
            <code style="font-size:12px;color:var(--td);background:var(--bd);padding:4px 8px;border-radius:6px">sk_test_****...b3c1</code>
        </div>
        <button type="button" class="btn btn-pr">+ مفتاح جديد</button>

        <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--bg)">
            <h4 style="font-weight:700;font-size:14px;margin-bottom:12px">🔒 تغيير كلمة المرور</h4>
            <form method="POST" action="<?php echo e(route('settings.password')); ?>">
                <?php echo csrf_field(); ?>
                <div style="margin-bottom:12px"><label class="form-label">كلمة المرور الحالية</label><input type="password" name="current_password" class="form-input" required></div>
                <div style="margin-bottom:12px"><label class="form-label">كلمة المرور الجديدة</label><input type="password" name="password" class="form-input" required></div>
                <div style="margin-bottom:12px"><label class="form-label">تأكيد كلمة المرور</label><input type="password" name="password_confirmation" class="form-input" required></div>
                <button type="submit" class="btn btn-s">تحديث كلمة المرور</button>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/settings/index.blade.php ENDPATH**/ ?>