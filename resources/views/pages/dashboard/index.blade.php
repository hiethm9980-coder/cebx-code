@extends('layouts.app')
@section('title', 'لوحة التحكم')
@section('content')
<x-page-header title="لوحة التحكم" subtitle="نظرة عامة على العمليات" />

<div class="stats-grid">
    <x-stat-card icon="📦" label="الشحنات" :value="$shipmentsCount" trend="+12%" :up="true" />
    <x-stat-card icon="🛒" label="الطلبات" :value="$ordersCount" trend="+8%" :up="true" />
    <x-stat-card icon="💰" label="الرصيد" :value="number_format($walletBalance) . ' ر.س'" />
    <x-stat-card icon="🔔" label="إشعارات" :value="$unreadNotifs" />
</div>

<div class="grid-2-1">
    <x-card title="أداء الشحنات">
        <div class="bar-chart">
            @foreach($monthlyData as $month)
                <div class="bar-col">
                    <div class="bar-value">{{ $month['count'] }}</div>
                    <div class="bar" style="height:{{ max(10, ($month['count'] / max(1, $maxMonthly)) * 140) }}px;background:linear-gradient(180deg,var(--pr),rgba(59,130,246,0.4))"></div>
                    <span class="bar-label">{{ $month['name'] }}</span>
                </div>
            @endforeach
        </div>
    </x-card>

    <x-card title="الناقلين">
        @foreach($carrierStats as $carrier)
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                <span style="width:55px;font-size:11px;color:var(--tm)">{{ $carrier['name'] }}</span>
                <div class="progress-bar" style="flex:1">
                    <div class="progress-fill" style="width:{{ $carrier['percent'] }}%;background:{{ $carrier['color'] }}"></div>
                </div>
                <span style="font-size:11px;font-weight:600;width:30px">{{ $carrier['percent'] }}%</span>
            </div>
        @endforeach
    </x-card>
</div>

<x-card title="آخر الشحنات">
    <div class="table-wrap">
        <table>
            <thead><tr><th>الرقم</th><th>الناقل</th><th>الحالة</th><th>العميل</th><th>المسار</th><th>التاريخ</th></tr></thead>
            <tbody>
                @foreach($recentShipments as $s)
                    <tr>
                        <td><a href="{{ route('shipments.show', $s) }}" class="td-link">{{ $s->tracking_number }}</a></td>
                        <td><span class="badge badge-in">{{ $s->carrier_code }}</span></td>
                        <td><x-badge :status="$s->status" /></td>
                        <td>{{ $s->recipient_name }}</td>
                        <td>{{ $s->sender_city ?? '—' }} → {{ $s->recipient_city ?? '—' }}</td>
                        <td>{{ $s->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@endsection
