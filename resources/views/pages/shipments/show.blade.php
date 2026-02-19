@extends('layouts.app')
@section('title', 'تفاصيل الشحنة')
@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
    <a href="{{ route('shipments.index') }}" class="btn btn-s">→ رجوع</a>
    <div>
        <h1 style="color:var(--tx);font-size:20px;font-weight:700">تفاصيل {{ $shipment->tracking_number }}</h1>
        <p style="color:var(--tm);font-size:11px">تتبع: {{ $shipment->carrier_shipment_id ?? $shipment->tracking_number }}</p>
    </div>
</div>

@if(!in_array($shipment->status, ['cancelled', 'delivered']))
<div style="display:flex;gap:7px;margin-bottom:16px">
    <a href="{{ route('shipments.label', $shipment) }}" class="btn btn-s">🖨 طباعة</a>
    <form action="{{ route('shipments.return', $shipment) }}" method="POST" style="display:inline">@csrf <button class="btn btn-pp">↩ مرتجع</button></form>
    <form action="{{ route('shipments.cancel', $shipment) }}" method="POST" data-confirm="إلغاء الشحنة؟" style="display:inline">@csrf @method('PATCH') <button class="btn btn-dg">✕ إلغاء</button></form>
</div>
@endif

<div class="grid-2">
    <x-card title="المرسل">
        <x-info-row label="الاسم" :value="$shipment->sender_name ?? '—'" />
        <x-info-row label="الهاتف" :value="$shipment->sender_phone ?? '—'" mono />
        <x-info-row label="العنوان" :value="($shipment->sender_city ?? '—') . ' — ' . ($shipment->sender_address_1 ?? '')" />
    </x-card>
    <x-card title="المستلم">
        <x-info-row label="الاسم" :value="$shipment->recipient_name" />
        <x-info-row label="الهاتف" :value="$shipment->recipient_phone ?? '—'" mono />
        <x-info-row label="العنوان" :value="($shipment->recipient_city ?? '—') . ' — ' . ($shipment->recipient_address_1 ?? '')" />
    </x-card>
</div>

<div class="grid-2">
    <x-card title="الشحنة">
        <x-info-row label="الناقل" :value="$shipment->carrier_code" />
        <x-info-row label="الوزن" :value="($shipment->total_weight ?? 0) . ' كغ'" />
        <x-info-row label="الأبعاد" :value="data_get($shipment->metadata, 'dimensions', '—')" />
        <x-info-row label="الخدمة" :value="$shipment->service_name ?? $shipment->service_code ?? '—'" />
        <x-info-row label="الطرود" :value="$shipment->parcels_count ?? 1" />
        <x-info-row label="تأمين" :value="$shipment->is_insured ? 'نعم' : 'لا'" />
    </x-card>
    <x-card title="التكاليف">
        <x-info-row label="تكلفة الناقل" :value="number_format($shipment->shipping_rate ?? 0, 2) . ' ر.س'" mono />
        <x-info-row label="سعر العميل" :value="number_format($shipment->total_charge ?? 0, 2) . ' ر.س'" mono />
        <x-info-row label="الربح" :value="number_format(($shipment->total_charge ?? 0) - ($shipment->shipping_rate ?? 0), 2) . ' ر.س'" color="var(--ac)" />
        <x-info-row label="التاريخ" :value="$shipment->created_at->format('Y-m-d')" />
        <x-info-row label="الحالة" :value="''" />
        <x-badge :status="$shipment->status" />
    </x-card>
</div>

<x-card title="مسار التتبع">
    <div class="timeline">
        @foreach($timeline as $step)
            <div class="timeline-step {{ $step['done'] ? 'done' : 'pending' }}">
                <div class="timeline-dot {{ $step['done'] ? 'done' : 'pending' }}">
                    {{ $step['done'] ? '✓' : '○' }}
                </div>
                <div>
                    <p class="timeline-title" style="color:{{ $step['done'] ? 'var(--tx)' : 'var(--td)' }}">{{ $step['title'] }}</p>
                    <p class="timeline-date">{{ $step['date'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</x-card>
@endsection
