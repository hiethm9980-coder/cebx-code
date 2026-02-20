{{-- resources/views/components/badge.blade.php --}}
@props(['status'])
@php
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
@endphp
<span class="badge {{ $s[1] }}">{{ $s[0] }}</span>
