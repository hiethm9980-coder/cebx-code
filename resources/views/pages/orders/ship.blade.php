@extends('layouts.app')
@section('title', 'تأكيد شحن الطلب')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:18px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('orders.index') }}" style="color:inherit;text-decoration:none">الطلبات</a>
            <span style="margin:0 6px">/</span>
            <span>تأكيد الشحن</span>
        </div>
        <h1 style="font-size:24px;font-weight:900;color:var(--tx);margin:0">تأكيد شحن الطلب</h1>
        <p style="margin:8px 0 0;color:var(--td);line-height:1.8;max-width:760px">
            أنت على وشك شحن الطلب <span class="td-mono" style="font-weight:800">{{ $order->order_number ?? $order->platform_order_id ?? $order->id }}</span>.
        </p>
    </div>
</div>

<x-card>
    <div style="display:grid;gap:10px">
        <div style="display:flex;gap:10px;flex-wrap:wrap;color:var(--td);font-size:14px">
            <div><strong style="color:var(--tx)">المبلغ:</strong> SAR {{ number_format((float) ($order->total_amount ?? 0), 2) }}</div>
            <div><strong style="color:var(--tx)">الحالة:</strong> {{ $order->status ?? '—' }}</div>
            <div><strong style="color:var(--tx)">العميل:</strong> {{ $order->customer_name ?? '—' }}</div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;align-items:center">
            <form method="POST" action="{{ route('orders.ship', $order) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-pr">تأكيد الشحن</button>
            </form>

            <a href="{{ route('orders.index') }}" class="btn btn-ghost">العودة للطلبات</a>

            @if(! in_array($order->status, [\App\Models\Order::STATUS_SHIPPED, \App\Models\Order::STATUS_DELIVERED, \App\Models\Order::STATUS_CANCELLED], true))
                <form method="POST" action="{{ route('orders.cancel', $order) }}" style="display:inline" onsubmit="return confirm('إلغاء الطلب بالكامل وليس فقط التراجع عن الشحن؟');">
                    @csrf
                    <button type="submit" class="btn btn-ghost" style="border:1px solid var(--bd);color:var(--dg)">إلغاء الطلب</button>
                </form>
            @endif
        </div>
    </div>
</x-card>
@endsection

