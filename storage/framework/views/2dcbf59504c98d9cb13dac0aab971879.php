<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status' => 'pending']));

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

foreach (array_filter((['status' => 'pending']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
$map = [
    'delivered'        => ['تم التسليم', 'badge-ac'],
    'in_transit'       => ['في الطريق', 'badge-in'],
    'out_for_delivery' => ['خرج للتوصيل', 'badge-pp'],
    'pending'          => ['قيد الانتظار', 'badge-wn'],
    'processing'       => ['قيد المعالجة', 'badge-wn'],
    'shipped'          => ['تم الشحن', 'badge-in'],
    'cancelled'        => ['ملغي', 'badge-dg'],
    'returned'         => ['مرتجع', 'badge-dg'],
    'new'              => ['جديد', 'badge-in'],
    'open'             => ['مفتوحة', 'badge-ac'],
    'resolved'         => ['تم الحل', 'badge-pp'],
    'closed'           => ['مغلقة', 'badge-td'],
    'in_progress'      => ['قيد المعالجة', 'badge-in'],
    'connected'        => ['متصل', 'badge-ac'],
    'disconnected'     => ['غير متصل', 'badge-dg'],
    'accepted'         => ['مقبولة', 'badge-ac'],
    'expired'          => ['منتهية', 'badge-td'],
    'active'           => ['نشط', 'badge-ac'],
    'completed'        => ['مكتمل', 'badge-ac'],
    'failed'           => ['فشل', 'badge-dg'],
    'verified'         => ['موثق', 'badge-ac'],
    'rejected'         => ['مرفوض', 'badge-dg'],
    'under_review'     => ['قيد المراجعة', 'badge-in'],
    'cleared'          => ['تم التخليص', 'badge-ac'],
    'held'             => ['محتجزة', 'badge-dg'],
    'scheduled'        => ['مجدول', 'badge-in'],
    'departed'         => ['انطلقت', 'badge-pp'],
    'arrived'          => ['وصلت', 'badge-ac'],
    'delayed'          => ['متأخرة', 'badge-dg'],
];
$s = $map[$status] ?? [$status, 'badge-td'];
?>
<span class="badge <?php echo e($s[1]); ?>"><?php echo e($s[0]); ?></span>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\cebx-code\resources\views/components/badge.blade.php ENDPATH**/ ?>