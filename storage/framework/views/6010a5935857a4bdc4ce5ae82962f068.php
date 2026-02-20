
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon' => '', 'label', 'value', 'trend' => null, 'up' => true]));

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

foreach (array_filter((['icon' => '', 'label', 'value', 'trend' => null, 'up' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="stat-card">
    <div class="label"><?php echo e($icon); ?> <?php echo e($label); ?></div>
    <div class="value"><?php echo e($value); ?></div>
    <?php if($trend): ?>
        <div class="trend <?php echo e($up ? 'up' : 'down'); ?>"><?php echo e($up ? '↑' : '↓'); ?> <?php echo e($trend); ?></div>
    <?php endif; ?>
</div>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/stat-card.blade.php ENDPATH**/ ?>