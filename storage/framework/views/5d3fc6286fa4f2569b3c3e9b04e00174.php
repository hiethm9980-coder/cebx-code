<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['type' => 'success', 'message']));

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

foreach (array_filter((['type' => 'success', 'message']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="toast-container">
    <div class="toast toast-<?php echo e($type); ?>">
        <?php echo e($message); ?>

        <button style="background:none;border:none;color:#fff;cursor:pointer" onclick="this.parentElement.remove()">✕</button>
    </div>
</div>
<?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/components/toast.blade.php ENDPATH**/ ?>