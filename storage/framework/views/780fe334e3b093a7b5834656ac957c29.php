<?php $__env->startSection('title', 'دفتر العناوين'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">📒 دفتر العناوين</h1>
    <button type="button" class="btn btn-pr" data-modal-open="addAddress">+ عنوان جديد</button>
</div>

<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px">
    <?php $__empty_1 = true; $__currentLoopData = $addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
                <div>
                    <span style="font-weight:700;font-size:15px"><?php echo e($addr->name); ?></span>
                    <?php if($addr->label): ?>
                        <span class="badge badge-in" style="margin-right:8px"><?php echo e($addr->label); ?></span>
                    <?php endif; ?>
                    <?php if($addr->is_default): ?>
                        <span class="badge badge-ac">افتراضي</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:6px">
                    <?php if(!$addr->is_default): ?>
                        <form method="POST" action="<?php echo e(route('addresses.default', $addr)); ?>" style="display:inline"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <button type="submit" class="btn btn-s" style="font-size:11px;padding:3px 10px">تعيين افتراضي</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('addresses.destroy', $addr)); ?>" style="display:inline"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-dg" style="font-size:11px;padding:3px 10px">حذف</button>
                    </form>
                </div>
            </div>
            <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الهاتف','value' => $addr->phone]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الهاتف','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($addr->phone)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $attributes = $__attributesOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__attributesOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $component = $__componentOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__componentOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'المدينة','value' => $addr->city]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'المدينة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($addr->city)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $attributes = $__attributesOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__attributesOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $component = $__componentOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__componentOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
            <?php if($addr->district): ?><?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الحي','value' => $addr->district]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الحي','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($addr->district)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $attributes = $__attributesOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__attributesOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $component = $__componentOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__componentOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?><?php endif; ?>
            <?php if($addr->street): ?><?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الشارع','value' => $addr->street]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الشارع','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($addr->street)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $attributes = $__attributesOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__attributesOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $component = $__componentOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__componentOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?><?php endif; ?>
            <?php if($addr->postal_code): ?><?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الرمز البريدي','value' => $addr->postal_code]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الرمز البريدي','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($addr->postal_code)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $attributes = $__attributesOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__attributesOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalffc14a94d295dd3a8012d841da97029c)): ?>
<?php $component = $__componentOriginalffc14a94d295dd3a8012d841da97029c; ?>
<?php unset($__componentOriginalffc14a94d295dd3a8012d841da97029c); ?>
<?php endif; ?><?php endif; ?>
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
        <div class="empty-state" style="grid-column:1/-1;padding:60px">لا توجد عناوين محفوظة</div>
    <?php endif; ?>
</div>

<?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['id' => 'addAddress','title' => 'إضافة عنوان جديد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'addAddress','title' => 'إضافة عنوان جديد']); ?>
    <form method="POST" action="<?php echo e(route('addresses.store')); ?>">
        <?php echo csrf_field(); ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div><label class="form-label">التسمية</label><input type="text" name="label" class="form-input" placeholder="المنزل, المكتب"></div>
            <div><label class="form-label">الاسم</label><input type="text" name="name" class="form-input" required></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div><label class="form-label">الهاتف</label><input type="text" name="phone" class="form-input" required></div>
            <div><label class="form-label">المدينة</label><input type="text" name="city" class="form-input" required></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div><label class="form-label">الحي</label><input type="text" name="district" class="form-input"></div>
            <div><label class="form-label">الرمز البريدي</label><input type="text" name="postal_code" class="form-input"></div>
        </div>
        <div style="margin-bottom:16px"><label class="form-label">الشارع</label><input type="text" name="street" class="form-input"></div>
        <button type="submit" class="btn btn-pr" style="width:100%">حفظ العنوان</button>
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