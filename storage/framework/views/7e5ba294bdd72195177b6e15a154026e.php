
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['items' => [], 'teal' => false]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['items' => [], 'teal' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="timeline" style="position:relative;padding-right:24px">
    <div style="position:absolute;right:7px;top:0;bottom:0;width:2px;background:var(--bd)"></div>
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div style="display:flex;gap:16px;margin-bottom:<?php echo e($loop->last ? '0' : '24px'); ?>;position:relative">
            <div style="width:16px;height:16px;border-radius:50%;flex-shrink:0;margin-top:2px;
                background:<?php echo e($loop->first ? ($teal ? '#0D9488' : 'var(--pr)') : 'var(--bd)'); ?>;
                <?php echo e($loop->first ? 'border:3px solid ' . ($teal ? 'rgba(13,148,136,0.2)' : 'rgba(59,130,246,0.2)') : ''); ?>">
            </div>
            <div style="flex:1">
                <div style="font-size:13px;font-weight:600;color:<?php echo e($loop->first ? 'var(--tx)' : 'var(--tm)'); ?>">
                    <?php echo e($item['title']); ?>

                </div>
                <div style="font-size:12px;color:var(--td);margin-top:2px"><?php echo e($item['date']); ?></div>
                <?php if(isset($item['location'])): ?>
                    <div style="font-size:12px;color:var(--td);margin-top:2px">📍 <?php echo e($item['location']); ?></div>
                <?php endif; ?>
                <?php if(isset($item['desc'])): ?>
                    <div style="font-size:12px;color:var(--td);margin-top:2px"><?php echo e($item['desc']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/timeline.blade.php ENDPATH**/ ?>