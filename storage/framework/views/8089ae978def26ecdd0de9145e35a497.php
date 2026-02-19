<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status', 'text' => null]));

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

foreach (array_filter((['status', 'text' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
$labels = [
    'processing' => 'قيد المعالجة', 'shipped' => 'تم الشحن', 'in_transit' => 'في الطريق',
    'delivered' => 'تم التسليم', 'cancelled' => 'ملغي', 'pending' => 'معلّق',
    'confirmed' => 'مؤكد', 'fulfilled' => 'مكتمل', 'active' => 'نشط',
    'suspended' => 'معطّل', 'open' => 'مفتوح', 'resolved' => 'محلول',
    'connected' => 'متصل', 'accepted' => 'مقبول', 'expired' => 'منتهي',
    'loading' => 'تحميل', 'sealed' => 'مختوم', 'intransit' => 'في الطريق',
    'cleared' => 'مخلّص', 'held' => 'محتجز', 'inspecting' => 'فحص',
    'review' => 'مراجعة', 'onduty' => 'في الخدمة', 'available' => 'متاح',
    'offduty' => 'خارج الخدمة', 'closed' => 'مغلق',
];
?>
<span class="badge st-<?php echo e($status); ?>"><?php echo e($text ?? ($labels[$status] ?? $status)); ?></span>
<?php /**PATH C:\xampp\htdocs\shipping-gateway-blade\resources\views/components/badge.blade.php ENDPATH**/ ?>