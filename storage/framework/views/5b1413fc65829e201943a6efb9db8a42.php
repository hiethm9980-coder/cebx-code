<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['id', 'title']));

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

foreach (array_filter((['id', 'title']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="modal-bg" id="<?php echo e($id); ?>" style="display:none">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2><?php echo e($title); ?></h2>
            <button class="btn btn-ghost" data-modal-close>✕</button>
        </div>
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH C:\Users\hamzah\Documents\shipping-gateway-blade\resources\views/components/modal.blade.php ENDPATH**/ ?>