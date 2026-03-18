@extends('layouts.app')
@section('title', 'تفاصيل السفينة')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">⛴️ تفاصيل السفينة</h1>
    <a href="{{ route('vessels.index') }}" class="btn btn-s">← العودة للسفن</a>
</div>

@php
    $name = $vessel->vessel_name ?? $vessel->name ?? '—';
    $type = $vessel->vessel_type ?? $vessel->type ?? '—';
    $stMap = ['at_sea' => ['🌊 في البحر', 'badge-in'], 'in_port' => ['⚓ في الميناء', 'badge-ac'], 'docked' => ['⚓ في الميناء', 'badge-ac'], 'maintenance' => ['🔧 صيانة', 'badge-wn'], 'idle' => ['⏸️ متوقفة', 'badge-td'], 'active' => ['✓ نشطة', 'badge-in'], 'decommissioned' => ['⛔ مُوقفة', 'badge-dg']];
    $st = $stMap[$vessel->status ?? ''] ?? ['—', 'badge-td'];
    $typeLabels = ['container' => 'حاوية', 'bulk' => 'بضائع سائبة', 'tanker' => 'ناقلة', 'roro' => 'رو-رو', 'general' => 'عام'];
    $typeLabel = $typeLabels[$type] ?? $type;
@endphp

{{-- ═══ معلومات السفينة الأساسية ═══ --}}
<x-card title="معلومات السفينة">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px">
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">اسم السفينة</div>
            <div style="font-weight:600;font-size:16px">{{ $name }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">رقم IMO</div>
            <div class="td-mono">{{ $vessel->imo_number ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">النوع</div>
            <div>{{ $typeLabel }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">الحمولة (TEU)</div>
            <div>{{ number_format($vessel->capacity_teu ?? 0) }} TEU</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">العلم</div>
            <div>{{ $vessel->flag ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">المشغّل</div>
            <div>{{ $vessel->operator ?? $vessel->owner_company ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">الموقع الحالي</div>
            <div>{{ $vessel->current_location ?? '—' }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">الحالة</div>
            <span class="badge {{ $st[1] }}">{{ $st[0] }}</span>
        </div>
    </div>
    @if(\Schema::hasColumn('vessels', 'mmsi') && $vessel->mmsi)
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--bg)">
            <div style="font-size:12px;color:var(--tm);margin-bottom:4px">MMSI</div>
            <div class="td-mono">{{ $vessel->mmsi }}</div>
        </div>
    @endif
</x-card>

{{-- ═══ جداول الرحلات (إن وجدت) ═══ --}}
@if(($hasSchedules ?? false) && isset($vessel->vesselSchedules) && $vessel->vesselSchedules->isNotEmpty())
<x-card title="📅 جداول الرحلات الأخيرة">
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>رقم الرحلة</th><th>المسار</th><th>المغادرة</th><th>الوصول</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @foreach($vessel->vesselSchedules as $sched)
                    <tr>
                        <td class="td-mono">{{ $sched->voyage_number ?? '—' }}</td>
                        <td>{{ $sched->service_route ?? ($sched->port_of_loading ?? '') . ' → ' . ($sched->port_of_discharge ?? '') }}</td>
                        <td>{{ $sched->etd?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $sched->eta?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @php
                                $schSt = ['scheduled' => 'مجدول', 'departed' => 'غادر', 'in_transit' => 'في الطريق', 'arrived' => 'وصل', 'cancelled' => 'ملغي'];
                            @endphp
                            <span class="badge badge-in">{{ $schSt[$sched->status ?? ''] ?? $sched->status ?? '—' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>
@elseif($hasSchedules ?? false)
<x-card title="📅 جداول الرحلات">
    <div class="empty-state" style="padding:32px;text-align:center;color:var(--tm)">لا توجد رحلات مسجّلة لهذه السفينة</div>
</x-card>
@endif
@endsection
