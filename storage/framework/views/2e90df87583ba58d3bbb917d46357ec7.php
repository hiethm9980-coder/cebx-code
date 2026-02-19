<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'color' => null, 'mono' => false]));

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

foreach (array_filter((['label', 'value', 'color' => null, 'mono' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="info-row">
    <span class="label"><?php echo e($label); ?></span>
    <span class="value" <?php if($color): ?> style="color:<?php echo e($color); ?>" <?php endif; ?> <?php if($mono): ?> style="font-family:monospace;font-size:11px" <?php endif; ?>><?php echo e($value); ?></span>
</div>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\resources\views/components/info-row.blade.php ENDPATH**/ ?>