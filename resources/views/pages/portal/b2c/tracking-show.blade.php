@extends('layouts.app')
@section('title', 'بوابة الأفراد | تتبع الشحنة')

@section('content')
<div style="margin-bottom:24px">
    <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
        <a href="{{ route('b2c.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأفراد</a>
        <span style="margin:0 6px">/</span>
        <a href="{{ route('b2c.tracking.index') }}" style="color:inherit;text-decoration:none">التتبع</a>
        <span style="margin:0 6px">/</span>
        <span class="td-mono">{{ $trackingNumber }}</span>
    </div>
    <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">تفاصيل التتبع</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px">
    {{-- بيانات الشحنة --}}
    <x-card title="معلومات الشحنة">
        <table style="width:100%;font-size:14px;border-collapse:collapse">
            <tr><td style="padding:8px 0;color:var(--td)">رقم التتبع</td>
                <td style="padding:8px 0;font-weight:600;font-family:monospace">{{ $shipment->tracking_number ?? '—' }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">المرجع</td>
                <td style="padding:8px 0">{{ $shipment->reference_number ?? '—' }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">الحالة</td>
                <td style="padding:8px 0">
                    @php
                        $st = $shipment->status ?? '';
                        $stClass = match($st) {
                            'delivered' => 'badge-ac',
                            'cancelled', 'failed', 'returned', 'exception' => 'badge-dg',
                            'in_transit', 'out_for_delivery', 'picked_up' => 'badge-pp',
                            default => 'badge-wn',
                        };
                        $stLabel = match($st) {
                            'draft' => 'مسودة', 'validated' => 'تم التحقق', 'rated' => 'تم التسعير',
                            'payment_pending' => 'بانتظار الدفع', 'purchased' => 'تم الشراء',
                            'ready_for_pickup' => 'جاهز للاستلام', 'pending' => 'معلق',
                            'picked_up' => 'تم الاستلام', 'in_transit' => 'في الطريق',
                            'out_for_delivery' => 'خارج للتسليم', 'delivered' => 'تم التسليم',
                            'returned' => 'مرتجع', 'exception' => 'استثناء',
                            'cancelled' => 'ملغى', 'failed' => 'فشل',
                            default => $st ?: '—',
                        };
                    @endphp
                    <span class="badge {{ $stClass }}">{{ $stLabel }}</span>
                </td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">الناقل</td>
                <td style="padding:8px 0">{{ $shipment->carrier ?? $shipment->carrier_code ?? '—' }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">المستلم</td>
                <td style="padding:8px 0">{{ $shipment->recipient_name ?? '—' }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">الوجهة</td>
                <td style="padding:8px 0">{{ $shipment->recipient_city ?? '—' }}، {{ $shipment->recipient_country ?? '—' }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">تاريخ الإنشاء</td>
                <td style="padding:8px 0">{{ optional($shipment->created_at)->format('Y-m-d') ?? '—' }}</td></tr>
            @if($shipment->actual_delivery_at)
            <tr><td style="padding:8px 0;color:var(--td)">تاريخ التسليم</td>
                <td style="padding:8px 0;color:var(--ac);font-weight:600">{{ optional($shipment->actual_delivery_at)->format('Y-m-d H:i') }}</td></tr>
            @endif
        </table>
    </x-card>

    {{-- مسار التتبع --}}
    <div>
        <x-card title="مسار الشحنة">
            @if($events->isEmpty())
                <div class="empty-state">لا توجد أحداث تتبع مسجلة بعد. جرّب مراجعة لاحقًا.</div>
            @else
                <div style="position:relative;padding-right:24px">
                    @foreach($events as $event)
                        <div style="position:relative;margin-bottom:16px;padding-bottom:16px;{{ !$loop->last ? 'border-bottom:1px dashed var(--bd)' : '' }}">
                            <div style="font-weight:600;color:var(--tx);margin-bottom:4px">
                                {{ $event->description ?? $event->status_label ?? $event->event_type ?? '—' }}
                            </div>
                            <div style="font-size:12px;color:var(--tm)">
                                {{ optional($event->occurred_at ?? $event->created_at)->format('Y-m-d H:i') ?? '—' }}
                                @if($event->location ?? $event->location_description ?? null)
                                    • {{ $event->location ?? $event->location_description }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap">
    <a href="{{ route('b2c.tracking.index') }}" class="btn btn-sec">رجوع إلى التتبع</a>
    <a href="{{ route('b2c.shipments.show', $shipment->id) }}" class="btn btn-pr">فتح تفاصيل الشحنة</a>
</div>
@endsection
