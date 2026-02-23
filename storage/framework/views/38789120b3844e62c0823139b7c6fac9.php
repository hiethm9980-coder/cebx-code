<?php $__env->startSection('title', 'التسعير'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🏷️ التسعير</h1>
    <button class="btn btn-pr" data-modal-open="add-rule">+ قاعدة تسعير</button>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🏷️','label' => 'قواعد التسعير','value' => $rulesCount ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🏷️','label' => 'قواعد التسعير','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rulesCount ?? 0)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '🚚','label' => 'الناقلين المفعّلين','value' => $activeCarriers ?? 0]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '🚚','label' => 'الناقلين المفعّلين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeCarriers ?? 0)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['icon' => '📊','label' => 'متوسط السعر / كجم','value' => 'SAR ' . number_format($avgPricePerKg ?? 0, 2)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => '📊','label' => 'متوسط السعر / كجم','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('SAR ' . number_format($avgPricePerKg ?? 0, 2))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '💲 أسعار الناقلين']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '💲 أسعار الناقلين']); ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>الناقل</th><th>النوع</th><th>المنطقة</th><th>الوزن الأساسي (كجم)</th><th>السعر الأساسي</th><th>سعر الكجم الإضافي</th><th>الحالة</th><th></th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $pricingRules ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <span class="badge badge-in"><?php echo e($rule->carrier_code); ?></span>
                                <span><?php echo e($rule->carrier_name); ?></span>
                            </div>
                        </td>
                        <td><?php echo e($rule->service_type === 'domestic' ? '🇸🇦 محلي' : '🌍 دولي'); ?></td>
                        <td><?php echo e($rule->zone_name ?? 'الكل'); ?></td>
                        <td class="td-mono"><?php echo e($rule->base_weight ?? 1); ?> كجم</td>
                        <td style="font-weight:600">SAR <?php echo e(number_format($rule->base_price, 2)); ?></td>
                        <td class="td-mono">SAR <?php echo e(number_format($rule->extra_kg_price, 2)); ?></td>
                        <td>
                            <span style="color:<?php echo e($rule->is_active ? 'var(--ac)' : 'var(--dg)'); ?>">
                                ● <?php echo e($rule->is_active ? 'مفعّل' : 'معطّل'); ?>

                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px">
                                <button class="btn btn-s" style="font-size:12px">تعديل</button>
                                <button class="btn btn-s" style="font-size:12px;color:var(--dg)">حذف</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="empty-state">لا توجد قواعد تسعير</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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


<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📌 الرسوم الإضافية']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📌 الرسوم الإضافية']); ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>اسم الرسم</th><th>النوع</th><th>القيمة</th><th>ينطبق على</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $surcharges ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($sc->name); ?></td>
                        <td><?php echo e($sc->type === 'fixed' ? 'ثابت' : 'نسبة %'); ?></td>
                        <td class="td-mono"><?php echo e($sc->type === 'fixed' ? 'SAR ' . number_format($sc->value, 2) : $sc->value . '%'); ?></td>
                        <td><?php echo e($sc->applies_to ?? 'الكل'); ?></td>
                        <td><span style="color:<?php echo e($sc->is_active ? 'var(--ac)' : 'var(--dg)'); ?>">● <?php echo e($sc->is_active ? 'مفعّل' : 'معطّل'); ?></span></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="empty-state">لا توجد رسوم إضافية</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'add-rule','title' => 'إضافة قاعدة تسعير','wide' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'add-rule','title' => 'إضافة قاعدة تسعير','wide' => true]); ?>
    <form method="POST" action="<?php echo e(route('pricing.index')); ?>">
        <?php echo csrf_field(); ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div><label class="form-label">الناقل</label><select name="carrier_code" class="form-input"><option>-- اختر الناقل --</option></select></div>
            <div><label class="form-label">نوع الخدمة</label><select name="service_type" class="form-input"><option value="domestic">محلي</option><option value="international">دولي</option></select></div>
            <div><label class="form-label">المنطقة</label><input type="text" name="zone_name" class="form-input" placeholder="مثال: المنطقة الوسطى"></div>
            <div><label class="form-label">الوزن الأساسي (كجم)</label><input type="number" name="base_weight" class="form-input" value="1" step="0.5"></div>
            <div><label class="form-label">السعر الأساسي (SAR)</label><input type="number" name="base_price" class="form-input" step="0.01" placeholder="0.00"></div>
            <div><label class="form-label">سعر الكجم الإضافي (SAR)</label><input type="number" name="extra_kg_price" class="form-input" step="0.01" placeholder="0.00"></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">حفظ</button>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/pricing/index.blade.php ENDPATH**/ ?>