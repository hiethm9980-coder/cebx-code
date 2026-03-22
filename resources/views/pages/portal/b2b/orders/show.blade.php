@extends('layouts.app')
@section('title', 'بوابة الأعمال | تفاصيل الطلب')

@section('content')
<div style="margin-bottom:24px">
    <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
        <a href="{{ route('b2b.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأعمال</a>
        <span style="margin:0 6px">/</span>
        <a href="{{ route('b2b.orders.index') }}" style="color:inherit;text-decoration:none">الطلبات</a>
        <span style="margin:0 6px">/</span>
        <span>{{ $order->order_number }}</span>
    </div>
    <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">
        طلب رقم {{ $order->order_number }}
    </h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px">
    {{-- Order Summary --}}
    <div class="card" style="padding:20px">
        <h3 style="font-size:14px;font-weight:700;color:var(--tm);margin:0 0 12px">ملخص الطلب</h3>
        <table style="width:100%;font-size:14px;border-collapse:collapse">
            <tr><td style="padding:6px 0;color:var(--td)">رقم الطلب</td><td style="padding:6px 0;font-weight:600;text-align:end">{{ $order->order_number }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">المنصة</td><td style="padding:6px 0;font-weight:600;text-align:end">{{ $order->platform_order_id ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">الحالة</td><td style="padding:6px 0;text-align:end">
                <span class="badge {{ $order->status === 'shipped' || $order->status === 'delivered' ? 'badge-success' : ($order->status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">
                    {{ $order->status }}
                </span>
            </td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">المبلغ الإجمالي</td><td style="padding:6px 0;font-weight:600;text-align:end">{{ number_format((float) $order->total_amount, 2) }} ر.س</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">عدد المنتجات</td><td style="padding:6px 0;font-weight:600;text-align:end">{{ $order->items_count ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">تاريخ الإنشاء</td><td style="padding:6px 0;text-align:end">{{ $order->created_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>
        </table>
    </div>

    {{-- Customer Info --}}
    <div class="card" style="padding:20px">
        <h3 style="font-size:14px;font-weight:700;color:var(--tm);margin:0 0 12px">معلومات العميل</h3>
        <table style="width:100%;font-size:14px;border-collapse:collapse">
            <tr><td style="padding:6px 0;color:var(--td)">الاسم</td><td style="padding:6px 0;font-weight:600;text-align:end">{{ $order->customer_name ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">الهاتف</td><td style="padding:6px 0;text-align:end">{{ $order->customer_phone ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">المدينة</td><td style="padding:6px 0;text-align:end">{{ $order->customer_city ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">العنوان</td><td style="padding:6px 0;text-align:end">{{ $order->customer_address ?? '—' }}</td></tr>
        </table>
    </div>

    {{-- Store & Shipment --}}
    <div class="card" style="padding:20px">
        <h3 style="font-size:14px;font-weight:700;color:var(--tm);margin:0 0 12px">المتجر والشحنة</h3>
        <table style="width:100%;font-size:14px;border-collapse:collapse">
            <tr><td style="padding:6px 0;color:var(--td)">المتجر</td><td style="padding:6px 0;font-weight:600;text-align:end">{{ $order->store?->name ?? '—' }}</td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">المنصة</td><td style="padding:6px 0;text-align:end">{{ $order->store?->platform ?? '—' }}</td></tr>
            @if($order->shipment)
            <tr><td style="padding:6px 0;color:var(--td)">رقم التتبع</td><td style="padding:6px 0;text-align:end">
                <a href="{{ route('b2b.shipments.show', $order->shipment->id) }}" style="color:var(--pr)">
                    {{ $order->shipment->tracking_number ?? $order->shipment->reference_number }}
                </a>
            </td></tr>
            <tr><td style="padding:6px 0;color:var(--td)">حالة الشحنة</td><td style="padding:6px 0;text-align:end">{{ $order->shipment->status ?? '—' }}</td></tr>
            @else
            <tr><td colspan="2" style="padding:6px 0;color:var(--td);text-align:center">لم يتم إنشاء شحنة لهذا الطلب بعد</td></tr>
            @endif
        </table>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="{{ route('b2b.orders.index') }}" class="btn btn-sec">رجوع إلى الطلبات</a>
    @if(!$order->shipment)
        <a href="{{ route('b2b.shipments.create') }}" class="btn btn-pr">إنشاء شحنة</a>
    @endif
</div>
@endsection
