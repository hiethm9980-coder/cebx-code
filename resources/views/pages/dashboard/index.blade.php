@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('page-title', $portalType === 'b2c' ? 'الرئيسية' : 'لوحة التحكم')

@section('content')
{{-- ═══ HEADER ═══ --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px">
    <div>
        @if($portalType === 'b2c')
            <h1 style="font-size:26px;font-weight:700;color:var(--tx);margin:0">مرحباً 👋</h1>
            <p style="color:var(--td);font-size:14px;margin:6px 0 0">ماذا تريد أن تفعل اليوم؟</p>
        @else
            <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">لوحة التحكم</h1>
            <p style="color:var(--td);font-size:14px;margin:6px 0 0">مرحباً بك في بوابة الأعمال 👋</p>
        @endif
    </div>
    @if($portalType === 'b2b')
        <a href="{{ route('shipments.create') }}" class="btn btn-pr">📦 شحنة جديدة</a>
    @endif
</div>

{{-- ═══ STAT CARDS ═══ --}}
<div class="stats-grid" style="margin-bottom:28px">
    @if($portalType === 'b2c')
        <x-stat-card icon="📦" label="شحنات نشطة" :value="$activeShipments ?? 0" />
        <x-stat-card icon="✅" label="تم التسليم" :value="$deliveredShipments ?? 0" />
        <x-stat-card icon="💰" label="الرصيد" :value="number_format($walletBalance ?? 0)" />
    @else
        <x-stat-card icon="📦" label="شحنات اليوم" :value="$todayShipments ?? 0" :trend="($shipmentsTrend ?? 0) . '%'" :up="($shipmentsTrend ?? 0) > 0" />
        <x-stat-card icon="🛒" label="طلبات جديدة" :value="$newOrders ?? 0" :trend="($ordersTrend ?? 0) . '%'" :up="($ordersTrend ?? 0) > 0" />
        <x-stat-card icon="💰" label="الرصيد" :value="number_format($walletBalance ?? 0)" />
        <x-stat-card icon="🏪" label="المتاجر" :value="$storesCount ?? 0" />
        <x-stat-card icon="⚠️" label="استثناءات" :value="$exceptions ?? 0" />
    @endif
</div>

@if($portalType === 'b2b')
{{-- ═══ B2B: CHARTS ═══ --}}
<div class="grid-2-1" style="margin-bottom:28px">
    <x-card title="📊 أداء الشحنات (آخر 6 أشهر)">
        <div class="bar-chart" style="height:180px">
            @foreach($monthlyData ?? [] as $month)
                <div class="bar-col">
                    <span class="bar-label">{{ $month['count'] }}</span>
                    @php $barH = $maxMonthly ? ($month['count'] / $maxMonthly * 160) : 0; @endphp
                    <div class="bar" style="height:{{ $barH }}px;background:linear-gradient(180deg,var(--pr),rgba(59,130,246,0.25))"></div>
                    <span class="bar-label">{{ $month['name'] }}</span>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card title="📈 توزيع الحالات">
        @foreach($statusDistribution ?? [] as $stat)
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--tm);margin-bottom:6px">
                    <span>{{ $stat['label'] }}</span>
                    <span>{{ $stat['pct'] }}%</span>
                </div>
                <div style="height:6px;background:var(--bd);border-radius:3px">
                    <div style="height:100%;width:{{ $stat['pct'] }}%;background:{{ $stat['color'] }};border-radius:3px;transition:width 1s ease"></div>
                </div>
            </div>
        @endforeach
    </x-card>
</div>

{{-- B2B: QUICK ACTIONS --}}
<div class="grid-4" style="margin-bottom:28px">
    @foreach([
        ['icon' => '📦', 'label' => 'شحنة جديدة', 'desc' => 'إنشاء شحنة يدوياً', 'route' => 'shipments.create'],
        ['icon' => '🛒', 'label' => 'الطلبات', 'desc' => 'استيراد من المتاجر', 'route' => 'orders.index'],
        ['icon' => '💳', 'label' => 'شحن الرصيد', 'desc' => 'إضافة رصيد للمحفظة', 'route' => 'wallet.index'],
        ['icon' => '📊', 'label' => 'التقارير', 'desc' => 'عرض التحليلات', 'route' => 'reports.index'],
    ] as $action)
        <a href="{{ route($action['route']) }}" class="entity-card" style="text-align:center;text-decoration:none;cursor:pointer">
            <div style="font-size:32px;margin-bottom:10px">{{ $action['icon'] }}</div>
            <div style="font-weight:600;color:var(--tx);font-size:14px">{{ $action['label'] }}</div>
            <div style="color:var(--td);font-size:12px;margin-top:4px">{{ $action['desc'] }}</div>
        </a>
    @endforeach
</div>
@endif

@if($portalType === 'b2c')
{{-- ═══ B2C: NEW SHIPMENT CTA ═══ --}}
<x-card title="📦 شحنة جديدة">
    <div class="grid-3" style="margin-bottom:0">
        @foreach([
            ['icon' => '🇸🇦', 'title' => 'شحن محلي', 'desc' => 'داخل المملكة العربية السعودية'],
            ['icon' => '🌍', 'title' => 'شحن دولي', 'desc' => 'إلى أي دولة في العالم'],
            ['icon' => '↩️', 'title' => 'شحنة مرتجعة', 'desc' => 'إرجاع طرد إلى المرسل'],
        ] as $type)
            <a href="{{ route('shipments.create') }}" class="entity-card" style="text-align:center;text-decoration:none;cursor:pointer;padding:24px">
                <div style="font-size:40px;margin-bottom:12px">{{ $type['icon'] }}</div>
                <div style="font-weight:700;color:var(--tx);font-size:16px;margin-bottom:4px">{{ $type['title'] }}</div>
                <div style="color:var(--td);font-size:13px">{{ $type['desc'] }}</div>
            </a>
        @endforeach
    </div>
</x-card>

{{-- B2C: QUICK TRACKING --}}
<x-card title="🔍 تتبع شحنة">
    @php
        $trackBtnStyle = $portalType === 'b2c'
            ? 'height:56px;padding:0 28px;border-radius:14px;font-size:16px;background:#0D9488;color:#fff;border:none;cursor:pointer'
            : 'height:56px;padding:0 28px;border-radius:14px;font-size:16px';
    @endphp
    <form action="{{ route('tracking.index') }}" method="GET" style="display:flex;gap:12px">
        <div style="flex:1">
            <input type="text" name="tracking_number" placeholder="أدخل رقم التتبع..."
                   class="form-input form-input-lg" style="width:100%;height:56px;font-size:18px">
        </div>
        <button type="submit" class="btn btn-pr" style="{{ $trackBtnStyle }}">تتبع</button>
    </form>
</x-card>
@endif

{{-- ═══ RECENT SHIPMENTS ═══ --}}
@php
    $recentTitle = $portalType === 'b2c' ? '📦 شحناتي النشطة' : '📦 آخر الشحنات';
@endphp
<x-card :title="$recentTitle">
    <x-slot:action>
        <a href="{{ route('shipments.index') }}" class="btn btn-s">عرض الكل</a>
    </x-slot:action>

    @if($portalType === 'b2b')
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم التتبع</th>
                        <th>المستلم</th>
                        <th>الناقل</th>
                        <th>الوجهة</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentShipments ?? [] as $shipment)
                        <tr>
                            <td><a href="{{ route('shipments.show', $shipment) }}" class="td-link td-mono">{{ $shipment->reference_number }}</a></td>
                            <td>{{ $shipment->recipient_name }}</td>
                            <td><span class="badge badge-in">{{ $shipment->carrier_code }}</span></td>
                            <td>{{ $shipment->recipient_city }}</td>
                            <td><x-badge :status="$shipment->status" /></td>
                            <td>{{ $shipment->created_at->format('Y-m-d') }}</td>
                            <td><a href="{{ route('shipments.show', $shipment) }}" class="btn btn-s">عرض</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">لا توجد شحنات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:12px">
            @forelse($recentShipments ?? [] as $shipment)
                @php
                    if ($shipment->status === 'delivered') {
                        $iconBg = 'background:rgba(16,185,129,0.13)';
                        $emoji = '✅';
                    } elseif (in_array($shipment->status, ['shipped', 'in_transit'])) {
                        $iconBg = 'background:rgba(139,92,246,0.13)';
                        $emoji = '🚚';
                    } elseif ($shipment->status === 'out_for_delivery') {
                        $iconBg = 'background:rgba(59,130,246,0.13)';
                        $emoji = '🏃';
                    } else {
                        $iconBg = 'background:rgba(245,158,11,0.13)';
                        $emoji = '⏳';
                    }
                @endphp
                <a href="{{ route('shipments.show', $shipment) }}" style="text-decoration:none;display:flex;justify-content:space-between;align-items:center;padding:16px 20px;background:var(--sf);border-radius:12px;border:1px solid var(--bd);cursor:pointer">
                    <div style="display:flex;align-items:center;gap:14px">
                        <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;{{ $iconBg }}">
                            {{ $emoji }}
                        </div>
                        <div>
                            <div style="font-family:monospace;color:#0D9488;font-weight:600;font-size:14px">{{ $shipment->reference_number }}</div>
                            <div style="font-size:12px;color:var(--td);margin-top:2px">{{ $shipment->sender_city }} → {{ $shipment->recipient_city }} • {{ $shipment->carrier_code }}</div>
                        </div>
                    </div>
                    <div style="text-align:left">
                        <x-badge :status="$shipment->status" />
                        <div style="font-size:11px;color:var(--td);margin-top:6px">{{ $shipment->created_at->format('d/m') }}</div>
                    </div>
                </a>
            @empty
                <div class="empty-state">لا توجد شحنات نشطة</div>
            @endforelse
        </div>
    @endif

</x-card>
@endsection
