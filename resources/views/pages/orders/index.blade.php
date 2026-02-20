@extends('layouts.app')
@section('title', 'إدارة الطلبات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🛒 إدارة الطلبات</h1>
    <button class="btn btn-pr" onclick="syncOrders()">🔄 مزامنة الطلبات</button>
</div>

{{-- ═══ FILTERS ═══ --}}
<x-card>
    <form method="GET" action="{{ route('orders.index') }}" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px;align-items:end">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث برقم الطلب أو اسم العميل..." class="form-input">
        <select name="status" class="form-input">
            <option value="">كل الحالات</option>
            <option value="pending">جديد</option>
            <option value="ready">جاهز للشحن</option>
            <option value="shipped">تم الشحن</option>
        </select>
        <select name="store_id" class="form-input">
            <option value="">كل المتاجر</option>
            @foreach($stores ?? [] as $store)
                <option value="{{ $store->id }}">{{ $store->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-pr" style="height:42px">بحث</button>
    </form>
</x-card>

{{-- ═══ ORDERS TABLE ═══ --}}
<x-card>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>رقم الطلب</th><th>المتجر</th><th>العميل</th><th>المنتجات</th>
                <th>المبلغ</th><th>الحالة</th><th>التاريخ</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($orders ?? [] as $order)
                    @php
                        $platformIcons = ['salla' => '🟣', 'zid' => '🔵', 'shopify' => '🟢', 'woocommerce' => '🟠'];
                        $icon = $platformIcons[$order->source] ?? '📦';
                    @endphp
                    <tr>
                        <td class="td-mono" style="font-weight:600">{{ $order->external_order_number }}</td>
                        <td>{{ $icon }} {{ $order->store?->name ?? $order->source }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->items_count ?? '—' }} منتج</td>
                        <td style="font-family:monospace">{{ number_format($order->total_amount ?? 0) }} ر.س</td>
                        <td><x-badge :status="$order->status" /></td>
                        <td>{{ $order->created_at->format('d/m') }}</td>
                        <td>
                            @if(in_array($order->status, ['pending', 'ready']))
                                <form method="POST" action="{{ route('orders.ship', $order) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-pr btn-sm">🚚 شحن</button>
                                </form>
                            @else
                                <a href="{{ route('shipments.show', $order->shipment_id ?? '#') }}" class="btn btn-s">👁️</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty-state">لا توجد طلبات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(isset($orders) && method_exists($orders, 'links'))
        <div style="margin-top:14px">{{ $orders->links() }}</div>
    @endif
</x-card>
@endsection
