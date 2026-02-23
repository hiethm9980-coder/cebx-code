<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['icon' => '', 'label' => '', 'value' => '0', 'trend' => null, 'up' => true]));

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

foreach (array_filter((['icon' => '', 'label' => '', 'value' => '0', 'trend' => null, 'up' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="stat-card">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <span class="stat-icon"><?php echo e($icon); ?></span>
        <?php if($trend): ?>
            <span class="stat-trend <?php echo e($up ? 'up' : 'down'); ?>"><?php echo e($trend); ?></span>
        <?php endif; ?>
    </div>
    <div class="stat-value"><?php echo e($value); ?></div>
    <div class="stat-label"><?php echo e($label); ?></div>
</div>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/stat-card.blade.php ENDPATH**/ ?>