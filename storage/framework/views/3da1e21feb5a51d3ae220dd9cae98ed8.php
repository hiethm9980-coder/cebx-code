<?php $__env->startSection('title', 'تفاصيل الشحنة'); ?>

<?php $__env->startSection('content'); ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">تفاصيل الشحنة <?php echo e($portalType === 'b2b' ? '#' . $shipment->reference_number : ''); ?></h1>
    <div style="display:flex;gap:10px">
        <?php if($portalType === 'b2b' && $shipment->label_url): ?>
            <a href="<?php echo e(route('shipments.label', $shipment)); ?>" class="btn btn-s">🖨️ طباعة البوليصة</a>
        <?php endif; ?>
        <a href="<?php echo e(route('shipments.index')); ?>" class="btn btn-s">← <?php echo e($portalType === 'b2b' ? 'العودة' : 'رجوع'); ?></a>
    </div>
</div>


<?php
    $statusConfig = [
        'delivered' => ['label' => 'تم التسليم', 'color' => '#10B981', 'icon' => '✅', 'desc' => 'تم تسليم الشحنة بنجاح'],
        'in_transit' => ['label' => 'قيد الشحن', 'color' => '#8B5CF6', 'icon' => '🚚', 'desc' => 'الشحنة في الطريق إلى المستلم'],
        'out_for_delivery' => ['label' => 'خرج للتوصيل', 'color' => '#3B82F6', 'icon' => '🏃', 'desc' => 'المندوب في الطريق للتوصيل'],
        'processing' => ['label' => 'قيد المعالجة', 'color' => '#F59E0B', 'icon' => '⏳', 'desc' => 'جاري تجهيز الشحنة'],
        'cancelled' => ['label' => 'ملغي', 'color' => '#EF4444', 'icon' => '❌', 'desc' => 'تم إلغاء الشحنة'],
    ];
    $sc = $statusConfig[$shipment->status] ?? ['label' => $shipment->status, 'color' => '#64748B', 'icon' => '📦', 'desc' => ''];
?>
<div style="background:linear-gradient(135deg,<?php echo e($sc['color']); ?>33,<?php echo e($sc['color']); ?>11);border-radius:16px;padding:24px 28px;border:1px solid <?php echo e($sc['color']); ?>33;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center">
    <div style="display:flex;align-items:center;gap:16px">
        <div style="width:56px;height:56px;border-radius:50%;background:<?php echo e($sc['color']); ?>33;display:flex;align-items:center;justify-content:center;font-size:28px"><?php echo e($sc['icon']); ?></div>
        <div>
            <div style="font-weight:700;color:<?php echo e($sc['color']); ?>;font-size:18px"><?php echo e($sc['label']); ?></div>
            <div style="color:var(--tm);font-size:13px;margin-top:4px"><?php echo e($sc['desc']); ?></div>
        </div>
    </div>
    <div style="text-align:left">
        <div style="font-family:monospace;font-size:20px;color:var(--tx);font-weight:700"><?php echo e($shipment->reference_number); ?></div>
        <div style="font-size:12px;color:var(--td);margin-top:4px"><?php echo e($shipment->carrier_code); ?> • <?php echo e($shipment->service_name ?? $shipment->service_code); ?></div>
    </div>
</div>

<div class="grid-2-1">
    <div>
        
        <div class="grid-2" style="margin-bottom:20px">
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📤 المرسل']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📤 المرسل']); ?>
                <div style="font-weight:600;color:var(--tx);margin-bottom:8px"><?php echo e($shipment->sender_name); ?></div>
                <div style="font-size:13px;color:var(--tm);line-height:2">
                    📞 <?php echo e($shipment->sender_phone); ?><br>
                    📍 <?php echo e($shipment->sender_city); ?><?php echo e($shipment->sender_state ? ', ' . $shipment->sender_state : ''); ?><br>
                    🏠 <?php echo e($shipment->sender_address_1); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📥 المستلم']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📥 المستلم']); ?>
                <div style="font-weight:600;color:var(--tx);margin-bottom:8px"><?php echo e($shipment->recipient_name); ?></div>
                <div style="font-size:13px;color:var(--tm);line-height:2">
                    📞 <?php echo e($shipment->recipient_phone); ?><br>
                    📍 <?php echo e($shipment->recipient_city); ?><?php echo e($shipment->recipient_state ? ', ' . $shipment->recipient_state : ''); ?><br>
                    🏠 <?php echo e($shipment->recipient_address_1); ?>

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

        
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📦 تفاصيل الطرد']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📦 تفاصيل الطرد']); ?>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
                <?php $firstParcel = $shipment->parcels?->first(); ?>
                <?php $__currentLoopData = [
                    ['الوزن', ($shipment->total_weight ?? $shipment->weight ?? '—') . ' كغ'],
                    ['الأبعاد', ($firstParcel ? (($firstParcel->length ?? '—') . '×' . ($firstParcel->width ?? '—') . '×' . ($firstParcel->height ?? '—')) : '—')],
                    ['المحتوى', $firstParcel?->description ?? $shipment->content_description ?? '—'],
                    ['القطع', $shipment->parcels_count ?? $shipment->pieces ?? 1],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $detail): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="text-align:center;padding:16px;background:var(--sf);border-radius:10px">
                        <div style="font-size:12px;color:var(--td);margin-bottom:6px"><?php echo e($detail[0]); ?></div>
                        <div style="font-size:15px;font-weight:600;color:var(--tx)"><?php echo e($detail[1]); ?></div>
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

        
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '💰 '.e($portalType === 'b2b' ? 'التفاصيل المالية' : 'التكلفة').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '💰 '.e($portalType === 'b2b' ? 'التفاصيل المالية' : 'التكلفة').'']); ?>
            <?php
                $costItems = [['رسوم الشحن', $shipment->shipping_rate]];
                if($portalType === 'b2b' && $shipment->is_cod) $costItems[] = ['رسوم COD', 5.00];
                if($shipment->is_insured) $costItems[] = ['التأمين', $shipment->insurance_amount];
                $subtotal = array_sum(array_column($costItems, 1));
                $tax = $subtotal * 0.15;
                $costItems[] = ['الضريبة (15%)', $tax];
            ?>
            <?php $__currentLoopData = $costItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="info-row">
                    <span class="label"><?php echo e($item[0]); ?></span>
                    <span class="value" style="font-family:monospace"><?php echo e(number_format($item[1], 2)); ?> ر.س</span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex;justify-content:space-between;padding-top:14px;font-weight:700">
                <span style="color:var(--tx)">الإجمالي</span>
                <span style="color:<?php echo e($portalType === 'b2c' ? '#0D9488' : 'var(--pr)'); ?>;font-size:20px;font-family:monospace">
                    <?php echo e(number_format($shipment->total_charge ?? ($subtotal + $tax), 2)); ?> ر.س
                </span>
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

    <div>
        <?php if($portalType === 'b2b'): ?>
            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📋 معلومات إضافية']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📋 معلومات إضافية']); ?>
                <?php $__currentLoopData = [
                    ['الناقل', $shipment->carrier_code],
                    ['الخدمة', $shipment->service_name ?? $shipment->service_code ?? '—'],
                    ['COD', $shipment->is_cod ? number_format($shipment->cod_amount, 2) . ' ر.س' : '—'],
                    ['المصدر', $shipment->source],
                    ['تاريخ الإنشاء', $shipment->created_at->format('d/m/Y')],
                    ['آخر تحديث', $shipment->updated_at->format('d/m/Y')],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if (isset($component)) { $__componentOriginalffc14a94d295dd3a8012d841da97029c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalffc14a94d295dd3a8012d841da97029c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.info-row','data' => ['label' => $row[0],'value' => $row[1]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('info-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row[0]),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row[1])]); ?>
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
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
        <?php endif; ?>

        
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📍 سجل التتبع']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📍 سجل التتبع']); ?>
            <?php if (isset($component)) { $__componentOriginal93f2afea2d7941ca7799292711b7f46f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal93f2afea2d7941ca7799292711b7f46f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.timeline','data' => ['items' => $trackingHistory ?? [],'teal' => $portalType === 'b2c']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('timeline'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trackingHistory ?? []),'teal' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($portalType === 'b2c')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal93f2afea2d7941ca7799292711b7f46f)): ?>
<?php $attributes = $__attributesOriginal93f2afea2d7941ca7799292711b7f46f; ?>
<?php unset($__attributesOriginal93f2afea2d7941ca7799292711b7f46f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal93f2afea2d7941ca7799292711b7f46f)): ?>
<?php $component = $__componentOriginal93f2afea2d7941ca7799292711b7f46f; ?>
<?php unset($__componentOriginal93f2afea2d7941ca7799292711b7f46f); ?>
<?php endif; ?>
            <?php if($portalType === 'b2c'): ?>
                <a href="<?php echo e(route('tracking.index', ['tracking_number' => $shipment->tracking_number])); ?>" class="btn btn-pr" style="width:100%;margin-top:16px;text-align:center;background:#0D9488;display:block">📍 تتبع مباشر</a>
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

        <?php if($portalType === 'b2c'): ?>
            
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '📞 هل تحتاج مساعدة؟']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '📞 هل تحتاج مساعدة؟']); ?>
                <p style="font-size:13px;color:var(--tm);margin:0 0 16px">إذا واجهت أي مشكلة مع شحنتك، تواصل معنا</p>
                <a href="<?php echo e(route('support.index')); ?>" class="btn btn-pr" style="width:100%;text-align:center;margin-bottom:8px;background:#0D9488;display:block">💬 تواصل مع الدعم</a>
                <a href="tel:920000000" class="btn btn-s" style="width:100%;text-align:center;display:block">📞 اتصل بنا</a>
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
        <?php endif; ?>

        
        <?php if(!in_array($shipment->status, ['delivered', 'cancelled'])): ?>
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['title' => '⚡ إجراءات']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => '⚡ إجراءات']); ?>
                <?php if(!in_array($shipment->status, ['cancelled'])): ?>
                    <form method="POST" action="<?php echo e(route('shipments.cancel', $shipment)); ?>" style="margin-bottom:8px">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <button type="submit" class="btn btn-dg" style="width:100%" onclick="return confirm('هل أنت متأكد من الإلغاء؟')">❌ إلغاء الشحنة</button>
                    </form>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('shipments.return', $shipment)); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-wn" style="width:100%">↩️ طلب إرجاع</button>
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
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/pages/shipments/show.blade.php ENDPATH**/ ?>