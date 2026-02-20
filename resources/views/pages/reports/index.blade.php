@extends('layouts.app')
@section('title', 'التقارير والتحليلات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">📊 التقارير والتحليلات</h1>
    <a href="{{ route('reports.export', 'shipments') }}" class="btn btn-s">📥 تصدير PDF</a>
</div>

{{-- ═══ PERIOD FILTER ═══ --}}
<div style="display:flex;gap:8px;margin-bottom:24px">
    @foreach(['today' => 'اليوم', 'week' => 'أسبوع', 'month' => 'شهر', 'quarter' => 'ربع سنة', 'year' => 'سنة'] as $key => $label)
        <a href="{{ route('reports.index', ['period' => $key]) }}"
           class="btn {{ request('period', 'month') === $key ? 'btn-pr' : 'btn-s' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- ═══ KPIs ═══ --}}
<div class="stats-grid" style="margin-bottom:24px">
    <x-stat-card icon="📦" label="إجمالي الشحنات" :value="number_format($totalShipments ?? 0)" :trend="($shipmentsTrend ?? 0) . '%'" :up="($shipmentsTrend ?? 0) > 0" />
    <x-stat-card icon="✅" label="نسبة التسليم" :value="($deliveryRate ?? 0) . '%'" :trend="($deliveryRateTrend ?? 0) . '%'" :up="($deliveryRateTrend ?? 0) > 0" />
    <x-stat-card icon="💰" label="إجمالي التكلفة" :value="number_format($totalCost ?? 0)" />
    <x-stat-card icon="⏱️" label="متوسط وقت التسليم" :value="($avgDeliveryTime ?? 0) . ' يوم'" />
    <x-stat-card icon="↩️" label="نسبة الإرجاع" :value="($returnRate ?? 0) . '%'" />
</div>

<div class="grid-2-1" style="margin-bottom:20px">
    {{-- ═══ DAILY CHART ═══ --}}
    <x-card title="📈 حجم الشحنات اليومي">
        <div class="bar-chart" style="height:200px">
            @foreach($dailyData ?? [] as $day)
                <div class="bar-col">
                    <div class="bar" style="height:{{ $maxDaily ? ($day['count'] / $maxDaily * 180) : 0 }}px;background:linear-gradient(180deg,var(--pr),rgba(59,130,246,0.13));min-width:8px"></div>
                </div>
            @endforeach
        </div>
    </x-card>

    {{-- ═══ CARRIER DISTRIBUTION ═══ --}}
    <x-card title="🚚 توزيع الناقلين">
        @foreach($carrierStats ?? [] as $carrier)
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                <div style="width:12px;height:12px;border-radius:3px;background:{{ $carrier['color'] }};flex-shrink:0"></div>
                <span style="flex:1;font-size:13px;color:var(--tm)">{{ $carrier['name'] }}</span>
                <div style="width:100px;height:6px;background:var(--bd);border-radius:3px">
                    <div style="height:100%;width:{{ $carrier['percent'] }}%;background:{{ $carrier['color'] }};border-radius:3px"></div>
                </div>
                <span style="font-size:13px;color:var(--tx);font-family:monospace;width:36px;text-align:left">{{ $carrier['percent'] }}%</span>
            </div>
        @endforeach
    </x-card>
</div>

{{-- ═══ TOP DESTINATIONS ═══ --}}
<x-card title="🏙️ أكثر الوجهات">
    <div class="table-wrap">
        <table>
            <thead><tr><th>المدينة</th><th>الشحنات</th><th>نسبة التسليم</th><th>متوسط الوقت</th><th>التكلفة</th></tr></thead>
            <tbody>
                @forelse($topDestinations ?? [] as $dest)
                    <tr>
                        <td>{{ $dest['city'] }}</td>
                        <td>{{ $dest['count'] }}</td>
                        <td style="color:{{ $dest['rate'] >= 90 ? 'var(--ac)' : 'var(--wn)' }}">{{ $dest['rate'] }}%</td>
                        <td>{{ $dest['avg_time'] }} يوم</td>
                        <td style="font-family:monospace">{{ number_format($dest['cost']) }} ر.س</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">لا توجد بيانات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
