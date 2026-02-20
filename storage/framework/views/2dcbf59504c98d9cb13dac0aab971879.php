
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status']));

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

foreach (array_filter((['status']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $statusMap = [
        'pending' => ['قيد الانتظار', 'st-pending'],
        'processing' => ['قيد المعالجة', 'st-processing'],
        'shipped' => ['قيد الشحن', 'st-shipped'],
        'in_transit' => ['قيد الشحن', 'st-intransit'],
        'out_for_delivery' => ['خرج للتوصيل', 'st-shipped'],
        'delivered' => ['تم التسليم', 'st-delivered'],
        'cancelled' => ['ملغي', 'st-cancelled'],
        'returned' => ['مرتجع', 'st-cancelled'],
        'draft' => ['مسودة', 'badge-td'],
        'active' => ['نشط', 'st-active'],
        'open' => ['مفتوحة', 'st-open'],
        'closed' => ['مغلقة', 'badge-td'],
        'resolved' => ['تم الحل', 'st-resolved'],
        'connected' => ['متصل', 'st-connected'],
        'disconnected' => ['غير متصل', 'st-cancelled'],
        'accepted' => ['مقبولة', 'st-accepted'],
        'expired' => ['منتهية', 'st-expired'],
    ];
    $s = $statusMap[$status] ?? [$status, 'badge-td'];
?>
<span class="badge <?php echo e($s[1]); ?>"><?php echo e($s[0]); ?></span>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/badge.blade.php ENDPATH**/ ?>