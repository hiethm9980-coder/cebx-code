<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['id', 'title' => '', 'wide' => false]));

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

foreach (array_filter((['id', 'title' => '', 'wide' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<div class="modal-backdrop" id="modal-<?php echo e($id); ?>">
    <div class="modal <?php echo e($wide ? 'wide' : ''); ?>">
        <div class="modal-header">
            <h3><?php echo e($title); ?></h3>
            <button type="button" data-modal-close style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--td)">✕</button>
        </div>
        <div class="modal-body"><?php echo e($slot); ?></div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/modal.blade.php ENDPATH**/ ?>