@extends('layouts.app')
@section('title', 'الشحنات')
@section('content')
<x-page-header title="الشحنات" :subtitle="$shipments->total() . ' شحنة'">
    <button class="btn btn-pr" data-modal-open="create-shipment">+ إنشاء شحنة</button>
    <a href="{{ route('shipments.export') }}" class="btn btn-s">📥 تصدير</a>
</x-page-header>

{{-- Tabs --}}
<div class="tabs">
    <a href="{{ route('shipments.index') }}" class="tab-btn {{ !request('status') ? 'active' : '' }}">الكل <span class="count">{{ $totalCount }}</span></a>
    <a href="{{ route('shipments.index', ['status' => 'payment_pending']) }}" class="tab-btn {{ request('status') === 'payment_pending' ? 'active' : '' }}">بانتظار الدفع</a>
    <a href="{{ route('shipments.index', ['status' => 'in_transit']) }}" class="tab-btn {{ request('status') === 'in_transit' ? 'active' : '' }}">في الطريق</a>
    <a href="{{ route('shipments.index', ['status' => 'delivered']) }}" class="tab-btn {{ request('status') === 'delivered' ? 'active' : '' }}">مُسلّم</a>
    <a href="{{ route('shipments.index', ['status' => 'cancelled']) }}" class="tab-btn {{ request('status') === 'cancelled' ? 'active' : '' }}">ملغي</a>
</div>

{{-- Search --}}
<form method="GET" style="margin-bottom:14px">
    <input type="text" name="search" class="form-control" style="max-width:400px" placeholder="بحث بالرقم أو التتبع أو العميل..." value="{{ request('search') }}">
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>الرقم</th><th>التتبع</th><th>الناقل</th><th>الحالة</th><th>العميل</th><th>المسار</th><th>التكلفة</th><th>إجراء</th></tr></thead>
        <tbody>
            @forelse($shipments as $s)
                <tr>
                    <td><a href="{{ route('shipments.show', $s) }}" class="td-link">{{ $s->tracking_number }}</a></td>
                    <td class="td-mono">{{ $s->carrier_shipment_id ?? $s->tracking_number }}</td>
                    <td><span class="badge badge-in">{{ $s->carrier_code }}</span></td>
                    <td><x-badge :status="$s->status" /></td>
                    <td>{{ $s->recipient_name }}</td>
                    <td>{{ $s->sender_city ?? '—' }} → {{ $s->recipient_city ?? '—' }}</td>
                    <td style="font-family:monospace">{{ number_format($s->total_charge ?? 0, 2) }} ر.س</td>
                    <td class="td-actions">
                        <a href="{{ route('shipments.show', $s) }}" class="btn btn-ghost">👁</a>
                        @if(!in_array($s->status, ['cancelled', 'delivered']))
                            <form action="{{ route('shipments.cancel', $s) }}" method="POST" data-confirm="هل أنت متأكد من إلغاء الشحنة؟">
                                @csrf @method('PATCH')
                                <button class="btn btn-ghost" style="color:var(--dg)">✕</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty-state">لا توجد شحنات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:14px">{{ $shipments->links() }}</div>

{{-- Create Modal --}}
<x-modal id="create-shipment" title="إنشاء شحنة جديدة">
    <form method="POST" action="{{ route('shipments.store') }}">
        @csrf
        <div class="form-grid">
            <div class="form-group"><label class="form-label">اسم المستلم *</label><input name="recipient_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">الناقل</label>
                <select name="carrier_code" class="form-control">
                    @foreach(['DHL','Aramex','SMSA','FedEx','UPS','SPL'] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label class="form-label">مدينة المرسل *</label><input name="origin_city" class="form-control" required></div>
            <div class="form-group"><label class="form-label">مدينة المستلم *</label><input name="destination_city" class="form-control" required></div>
            <div class="form-group"><label class="form-label">الوزن (كغ)</label><input name="weight" type="number" step="0.1" class="form-control"></div>
            <div class="form-group"><label class="form-label">التكلفة</label><input name="total_cost" type="number" step="0.01" class="form-control"></div>
            <div class="form-group"><label class="form-label">الخدمة</label>
                <select name="service_type" class="form-control">
                    <option value="express">Express</option><option value="standard">Standard</option><option value="economy">Economy</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">الأبعاد</label><input name="dimensions" class="form-control" placeholder="30×20×15 سم"></div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:12px">إنشاء</button>
    </form>
</x-modal>
@endsection
