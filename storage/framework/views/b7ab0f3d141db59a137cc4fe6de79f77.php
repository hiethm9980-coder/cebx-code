<?php $__env->startSection('title', 'تفاصيل الشحنة'); ?>
<?php $__env->startSection('content'); ?>
<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
    <a href="<?php echo e(route('shipments.index')); ?>" class="btn btn-s">→ رجوع</a>
    <div>
        <h1 style="color:var(--tx);font-size:20px;font-weight:700">تفاصيل <?php echo e($shipment->tracking_number); ?></h1>
        <p style="color:var(--tm);font-size:11px">تتبع: <?php echo e($shipment->carrier_tracking_number); ?></p>
    </div>
</div>

<?php if(!in_array($shipment->status, ['cancelled', 'delivered'])): ?>
<div style="display:flex;gap:7px;margin-bottom:16px">
    <a href="<?php echo e(route('shipments.label', $shipment)); ?>" class="btn btn-s">🖨 طباعة</a>
    <form action="<?php echo e(route('shipments.return', $shipment)); ?>" method="POST" style="display:inline"><?php echo csrf_field(); ?> <button class="btn btn-pp">↩ مرتجع</button></form>
    <form action="<?php echo e(route('shipments.cancel', $shipment)); ?>" method="POST" data-confirm="إلغاء الشحنة؟" style="display:inline"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?> <button class="btn btn-dg">✕ إلغاء</button></form>
</div>
<?php endif; ?>

<div class="grid-2">
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'المرسل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'المرسل']); ?>
        <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الاسم','value' => $shipment->sender_name ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الاسم','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->sender_name ?? '—')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الهاتف','value' => $shipment->sender_phone ?? '—','mono' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الهاتف','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->sender_phone ?? '—'),'mono' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'العنوان','value' => $shipment->origin_city . ' — ' . ($shipment->origin_address ?? '')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'العنوان','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->origin_city . ' — ' . ($shipment->origin_address ?? ''))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'المستلم']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'المستلم']); ?>
        <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الاسم','value' => $shipment->recipient_name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الاسم','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->recipient_name)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الهاتف','value' => $shipment->recipient_phone ?? '—','mono' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الهاتف','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->recipient_phone ?? '—'),'mono' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'العنوان','value' => $shipment->destination_city . ' — ' . ($shipment->destination_address ?? '')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'العنوان','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->destination_city . ' — ' . ($shipment->destination_address ?? ''))]); ?>
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

<div class="grid-2">
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'الشحنة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الشحنة']); ?>
        <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الناقل','value' => $shipment->carrier_code]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الناقل','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->carrier_code)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الوزن','value' => $shipment->weight . ' كغ']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الوزن','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->weight . ' كغ')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الأبعاد','value' => $shipment->dimensions ?? '—']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الأبعاد','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->dimensions ?? '—')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الخدمة','value' => $shipment->service_type]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الخدمة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->service_type)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الطرود','value' => $shipment->parcels_count ?? 1]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الطرود','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->parcels_count ?? 1)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'تأمين','value' => $shipment->has_insurance ? 'نعم' : 'لا']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'تأمين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->has_insurance ? 'نعم' : 'لا')]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'التكاليف']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'التكاليف']); ?>
        <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'تكلفة الناقل','value' => number_format($shipment->carrier_cost ?? 0, 2) . ' ر.س','mono' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'تكلفة الناقل','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($shipment->carrier_cost ?? 0, 2) . ' ر.س'),'mono' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'سعر العميل','value' => number_format($shipment->total_cost, 2) . ' ر.س','mono' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'سعر العميل','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($shipment->total_cost, 2) . ' ر.س'),'mono' => true]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الربح','value' => number_format(($shipment->total_cost - ($shipment->carrier_cost ?? 0)), 2) . ' ر.س','color' => 'var(--ac)']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الربح','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format(($shipment->total_cost - ($shipment->carrier_cost ?? 0)), 2) . ' ر.س'),'color' => 'var(--ac)']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'التاريخ','value' => $shipment->created_at->format('Y-m-d')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'التاريخ','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shipment->created_at->format('Y-m-d'))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => 'الحالة','value' => '']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'الحالة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('')]); ?>
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

<?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => 'مسار التتبع']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'مسار التتبع']); ?>
    <div class="timeline">
        <?php $__currentLoopData = $timeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="timeline-step <?php echo e($step['done'] ? 'done' : 'pending'); ?>">
                <div class="timeline-dot <?php echo e($step['done'] ? 'done' : 'pending'); ?>">
                    <?php echo e($step['done'] ? '✓' : '○'); ?>

                </div>
                <div>
                    <p class="timeline-title" style="color:<?php echo e($step['done'] ? 'var(--tx)' : 'var(--td)'); ?>"><?php echo e($step['title']); ?></p>
                    <p class="timeline-date"><?php echo e($step['date']); ?></p>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/pages/shipments/show.blade.php ENDPATH**/ ?>