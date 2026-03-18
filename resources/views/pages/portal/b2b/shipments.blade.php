@extends('layouts.app')
@section('title', 'بوابة الأعمال | الشحنات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('b2b.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأعمال</a>
            <span style="margin:0 6px">/</span>
            <span>الشحنات</span>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">لوحة تشغيل الشحنات</h1>
        <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:760px">
            هذه الصفحة مخصصة للفريق التشغيلي في <strong>{{ $account->name }}</strong>. راجع الحجم الحالي والمهام المفتوحة قبل الانتقال إلى إدارة الشحنات الكاملة.
        </p>
    </div>
    @if($canCreateShipment)
        <a href="{{ $createRoute }}" class="btn btn-pr">بدء طلب شحنة لفريقك</a>
    @endif
</div>

<div class="stats-grid" style="margin-bottom:24px">
    @foreach($stats as $stat)
        <x-stat-card :icon="$stat['icon']" :label="$stat['label']" :value="$stat['value']" />
    @endforeach
</div>

<div class="grid-2">
    <x-card title="آخر الشحنات">
        <div style="overflow:auto">
            <table class="table">
                <thead>
                <tr>
                    <th>المرجع</th>
                    <th>المستلم</th>
                    <th>الحالة</th>
                    <th>التكلفة</th>
                </tr>
                </thead>
                <tbody>
                @forelse($shipments as $shipment)
                    <tr>
                        <td class="td-mono">{{ $shipment->reference_number ?? $shipment->tracking_number ?? $shipment->id }}</td>
                        <td>{{ $shipment->recipient_name ?? '—' }}</td>
                        <td>{{ $shipment->status ?? '—' }}</td>
                        <td>{{ number_format((float) ($shipment->total_charge ?? 0), 2) }} {{ $shipment->currency ?? 'SAR' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">لا توجد شحنات بعد. استخدم إدارة الشحنات الكاملة لبدء التشغيل.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="تركيز الفريق اليوم">
        <div style="display:flex;flex-direction:column;gap:12px">
            <div style="padding:14px;border:1px solid var(--bd);border-radius:14px">
                <div style="font-weight:700;color:var(--tx)">مراجعة الطرود المفتوحة</div>
                <div style="color:var(--td);font-size:13px;margin-top:4px">التحقق من الشحنات بانتظار الشراء أو الالتقاط قبل نهاية اليوم التشغيلي.</div>
            </div>
            <div style="padding:14px;border:1px solid var(--bd);border-radius:14px">
                <div style="font-weight:700;color:var(--tx)">الانتقال للتفاصيل</div>
                <div style="color:var(--td);font-size:13px;margin-top:4px">زر «إدارة الشحنات بالكامل» ينقلك إلى الأدوات الكاملة للتصفية والتصدير والعمليات اليومية.</div>
            </div>
        </div>
    </x-card>
</div>
@endsection
