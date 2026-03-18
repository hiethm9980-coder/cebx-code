@extends('layouts.app')
@section('title', 'بوابة الأفراد | الشحنات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('b2c.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأفراد</a>
            <span style="margin:0 6px">/</span>
            <span>الشحنات</span>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">مساحة الشحنات الفردية</h1>
        <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:720px">
            راجع آخر شحناتك من حساب <strong>{{ $account->name }}</strong> بسرعة، ثم افتح مركز الشحنات الكامل عندما تحتاج إلى إنشاء شحنة أو متابعة التفاصيل التشغيلية.
        </p>
    </div>
    @if($canCreateShipment)
        <a href="{{ $createRoute }}" class="btn btn-pr">بدء طلب شحنة</a>
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
                    <th>الوجهة</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
                </thead>
                <tbody>
                @forelse($shipments as $shipment)
                    <tr>
                        <td class="td-mono">{{ $shipment->reference_number ?? $shipment->tracking_number ?? $shipment->id }}</td>
                        <td>{{ $shipment->recipient_city ?? 'غير محددة' }}</td>
                        <td>{{ $shipment->status ?? '—' }}</td>
                        <td>{{ optional($shipment->created_at)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">لا توجد شحنات حتى الآن. افتح مركز الشحنات لبدء أول طلب شحن.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card title="ما الذي يمكن عمله هنا؟">
        <div style="display:flex;flex-direction:column;gap:12px">
            <div style="padding:14px;border:1px solid var(--bd);border-radius:14px">
                <div style="font-weight:700;color:var(--tx)">متابعة سريعة</div>
                <div style="color:var(--td);font-size:13px;margin-top:4px">تحقق من أحدث شحناتك قبل الانتقال إلى صفحة التفاصيل الكاملة.</div>
            </div>
            <div style="padding:14px;border:1px solid var(--bd);border-radius:14px">
                <div style="font-weight:700;color:var(--tx)">إجراء واضح</div>
                <div style="color:var(--td);font-size:13px;margin-top:4px">استخدم زر «فتح مركز الشحنات» للوصول إلى إنشاء الشحنات والتصفية والتصدير.</div>
            </div>
            <div style="padding:14px;border:1px solid var(--bd);border-radius:14px;background:rgba(59,130,246,.06)">
                <div style="font-weight:700;color:var(--tx)">نصيحة</div>
                <div style="color:var(--td);font-size:13px;margin-top:4px">إذا كنت تتابع شحنة محددة الآن، استخدم صفحة التتبع من القائمة الجانبية للوصول الأسرع.</div>
            </div>
        </div>
    </x-card>
</div>
@endsection
