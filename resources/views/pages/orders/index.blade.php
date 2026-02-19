@extends('layouts.app')
@section('title', 'الطلبات')
@section('content')
<x-page-header title="الطلبات" :subtitle="$orders->total() . ' طلب'">
    <button class="btn btn-pr" data-modal-open="create-order">+ طلب يدوي</button>
</x-page-header>
<div class="table-wrap">
    <table>
        <thead><tr><th>الرقم</th><th>العميل</th><th>المبلغ</th><th>المنتجات</th><th>المصدر</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr></thead>
        <tbody>
            @forelse($orders as $o)
                <tr>
                    <td class="td-link">{{ $o->order_number }}</td>
                    <td>{{ $o->customer_name }}</td>
                    <td style="font-family:monospace">{{ number_format($o->total_amount, 2) }} ر.س</td>
                    <td>{{ $o->items_count ?? $o->items()->count() }}</td>
                    <td><span class="badge badge-in">{{ $o->store?->platform ?? 'يدوي' }}</span></td>
                    <td><x-badge :status="$o->status" /></td>
                    <td>{{ $o->created_at->format('Y-m-d') }}</td>
                    <td class="td-actions">
                        @if($o->status === 'pending')
                            <form action="{{ route('orders.ship', $o) }}" method="POST">@csrf <button class="btn btn-ac">🚚 شحن</button></form>
                            <form action="{{ route('orders.cancel', $o) }}" method="POST" data-confirm="إلغاء؟">@csrf @method('PATCH') <button class="btn btn-dg">✕</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-state">لا توجد طلبات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:14px">{{ $orders->links() }}</div>

<x-modal id="create-order" title="طلب يدوي جديد">
    <form method="POST" action="{{ route('orders.store') }}">@csrf
        <div class="form-grid">
            <div class="form-group"><label class="form-label">اسم العميل *</label><input name="customer_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">البريد</label><input name="customer_email" type="email" class="form-control"></div>
            <div class="form-group"><label class="form-label">المبلغ *</label><input name="total_amount" type="number" step="0.01" class="form-control" required></div>
            <div class="form-group"><label class="form-label">العنوان</label><input name="shipping_address" class="form-control"></div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:10px">إنشاء</button>
    </form>
</x-modal>
@endsection
