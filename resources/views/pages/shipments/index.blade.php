@extends('layouts.app')
@section('title', $portalType === 'b2c' ? 'شحناتي' : 'إدارة الشحنات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">📦 {{ $portalType === 'b2c' ? 'شحناتي' : 'إدارة الشحنات' }}</h1>
    <div style="display:flex;gap:10px">
        @if($portalType === 'b2b')
            <a href="{{ route('shipments.export') }}" class="btn btn-s">📥 تصدير</a>
        @endif
        <a href="{{ route('shipments.create') }}" class="btn btn-pr">+ شحنة جديدة</a>
    </div>
</div>

{{-- ═══ FILTERS ═══ --}}
<x-card>
    <form method="GET" action="{{ route('shipments.index') }}"
          style="display:grid;grid-template-columns:{{ $portalType === 'b2b' ? '2fr 1fr 1fr 1fr 1fr auto' : '1fr auto' }};gap:12px;align-items:end">
        <div>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="بحث برقم التتبع{{ $portalType === 'b2b' ? ' أو اسم المستلم' : '' }}..."
                   class="form-input" style="width:100%">
        </div>
        @if($portalType === 'b2b')
            <select name="status" class="form-input">
                <option value="">كل الحالات</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>قيد المعالجة</option>
                <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>تم الشحن</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>تم التسليم</option>
            </select>
            <select name="carrier" class="form-input">
                <option value="">كل الناقلين</option>
                <option value="aramex">أرامكس</option>
                <option value="smsa">سمسا</option>
                <option value="dhl">DHL</option>
                <option value="fedex">فيدكس</option>
            </select>
            <select name="source" class="form-input">
                <option value="">كل المصادر</option>
                <option value="direct">يدوي</option>
                <option value="order">طلب</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="form-input">
        @else
            <select name="status" class="form-input" style="width:auto">
                <option value="">كل الحالات</option>
                <option value="pending">نشطة</option>
                <option value="delivered">مسلّمة</option>
                <option value="cancelled">ملغية</option>
            </select>
        @endif
        <button type="submit" class="btn btn-pr" style="height:42px">بحث</button>
    </form>
</x-card>

{{-- ═══ SHIPMENTS LIST ═══ --}}
@if($portalType === 'b2b')
    {{-- B2B: TABLE VIEW --}}
    <x-card>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div style="display:flex;gap:8px;align-items:center">
                <input type="checkbox" id="selectAll">
                <label for="selectAll" style="font-size:13px;color:var(--td)">تحديد الكل</label>
                <button class="btn btn-s" style="margin-right:12px" onclick="window.print()">🖨️ طباعة البوالص</button>
            </div>
            <span style="font-size:13px;color:var(--td)">إجمالي: {{ $shipments->total() }} شحنة</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:30px"></th>
                    <th>رقم التتبع</th><th>المستلم</th><th>هاتف</th><th>الناقل</th>
                    <th>الخدمة</th><th>المدينة</th><th>COD</th><th>الوزن</th>
                    <th>الحالة</th><th>التاريخ</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($shipments as $shipment)
                        <tr>
                            <td><input type="checkbox" name="selected[]" value="{{ $shipment->id }}"></td>
                            <td><a href="{{ route('shipments.show', $shipment) }}" class="td-link td-mono">{{ $shipment->reference_number }}</a></td>
                            <td>{{ $shipment->recipient_name }}</td>
                            <td style="direction:ltr;text-align:right">{{ $shipment->recipient_phone }}</td>
                            <td><span class="badge badge-in">{{ $shipment->carrier_code ?? '—' }}</span></td>
                            <td>{{ $shipment->service_name ?? $shipment->service_code ?? '—' }}</td>
                            <td>{{ $shipment->recipient_city }}</td>
                            <td style="font-family:monospace">{{ $shipment->is_cod ? number_format($shipment->cod_amount) . ' ر.س' : '—' }}</td>
                            <td>{{ $shipment->total_weight ?? '—' }} كغ</td>
                            <td><x-badge :status="$shipment->status" /></td>
                            <td>{{ $shipment->created_at->format('d/m') }}</td>
                            <td><a href="{{ route('shipments.show', $shipment) }}" class="btn btn-s">👁️</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="empty-state">لا توجد شحنات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px">{{ $shipments->links() }}</div>
    </x-card>
@else
    {{-- B2C: CARD VIEW --}}
    <div style="display:flex;flex-direction:column;gap:14px">
        @forelse($shipments as $shipment)
            <a href="{{ route('shipments.show', $shipment) }}" style="text-decoration:none">
                <div class="entity-card" style="padding:20px 24px;cursor:pointer">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div style="display:flex;gap:16px;align-items:center">
                            <div style="width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;
                                @if($shipment->status === 'delivered') background:rgba(16,185,129,0.13)
                                @elseif(in_array($shipment->status, ['shipped','in_transit'])) background:rgba(139,92,246,0.13)
                                @elseif($shipment->status === 'out_for_delivery') background:rgba(59,130,246,0.13)
                                @elseif($shipment->status === 'cancelled') background:rgba(239,68,68,0.13)
                                @else background:rgba(245,158,11,0.13) @endif">
                                @if($shipment->status === 'delivered') ✅
                                @elseif(in_array($shipment->status, ['shipped','in_transit'])) 🚚
                                @elseif($shipment->status === 'out_for_delivery') 🏃
                                @elseif($shipment->status === 'cancelled') ❌
                                @else ⏳ @endif
                            </div>
                            <div>
                                <div style="font-family:monospace;color:#0D9488;font-weight:700;font-size:15px">{{ $shipment->reference_number }}</div>
                                <div style="font-size:13px;color:var(--tx);margin-top:4px">إلى: {{ $shipment->recipient_name }}</div>
                                <div style="font-size:12px;color:var(--td);margin-top:2px">📍 {{ $shipment->sender_city }} → {{ $shipment->recipient_city }} • {{ $shipment->carrier_code }}</div>
                            </div>
                        </div>
                        <div style="text-align:left">
                            <x-badge :status="$shipment->status" />
                            <div style="font-size:12px;color:var(--td);margin-top:8px">{{ $shipment->created_at->format('d/m') }}</div>
                            <div style="font-size:13px;font-family:monospace;color:var(--tx);margin-top:2px">{{ number_format($shipment->total_charge, 2) }} ر.س</div>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="empty-state">لا توجد شحنات</div>
        @endforelse
    </div>
    <div style="margin-top:20px">{{ $shipments->links() }}</div>
@endif
@endsection
