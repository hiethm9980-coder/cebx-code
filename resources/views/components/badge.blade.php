@props(['status', 'text' => null])
@php
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
@endphp
<span class="badge st-{{ $status }}">{{ $text ?? ($labels[$status] ?? $status) }}</span>
