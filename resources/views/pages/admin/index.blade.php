@extends('layouts.app')
@section('title', 'الإدارة العامة')

@section('content')
<div style="margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🛡️ الإدارة العامة</h1>
    <p style="color:var(--td);font-size:14px;margin:6px 0 0">إعدادات النظام والإدارة المركزية</p>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <x-stat-card icon="🏢" label="المنظمات" :value="$orgCount ?? 0" />
    <x-stat-card icon="👥" label="المستخدمون" :value="$usersCount ?? 0" />
    <x-stat-card icon="📦" label="إجمالي الشحنات" :value="number_format($totalShipments ?? 0)" />
    <x-stat-card icon="⚡" label="حالة النظام" value="متصل" />
</div>

{{-- Quick Access Grid --}}
<div class="grid-4" style="margin-bottom:28px">
    @foreach([
        ['icon' => '🏢', 'label' => 'المنظمات', 'desc' => 'إدارة الحسابات', 'route' => 'organizations.index'],
        ['icon' => '👥', 'label' => 'المستخدمون', 'desc' => 'إدارة المستخدمين', 'route' => 'users.index'],
        ['icon' => '🏷️', 'label' => 'التسعير', 'desc' => 'قواعد الأسعار', 'route' => 'pricing.index'],
        ['icon' => '📜', 'label' => 'التدقيق', 'desc' => 'سجل العمليات', 'route' => 'audit.index'],
        ['icon' => '🪪', 'label' => 'KYC', 'desc' => 'التحقق من الهوية', 'route' => 'kyc.index'],
        ['icon' => '☣️', 'label' => 'البضائع الخطرة', 'desc' => 'إدارة DG', 'route' => 'dg.index'],
        ['icon' => '⚠️', 'label' => 'المخاطر', 'desc' => 'تقييم المخاطر', 'route' => 'risk.index'],
        ['icon' => '💳', 'label' => 'المالية', 'desc' => 'العمليات المالية', 'route' => 'financial.index'],
    ] as $item)
        <a href="{{ route($item['route']) }}" class="entity-card" style="text-align:center;text-decoration:none;cursor:pointer">
            <div style="font-size:32px;margin-bottom:10px">{{ $item['icon'] }}</div>
            <div style="font-weight:600;color:var(--tx);font-size:14px">{{ $item['label'] }}</div>
            <div style="color:var(--td);font-size:12px;margin-top:4px">{{ $item['desc'] }}</div>
        </a>
    @endforeach
</div>

{{-- System Health --}}
<x-card title="⚡ حالة النظام">
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px">
        @foreach($systemHealth ?? [
            ['name' => 'قاعدة البيانات', 'status' => 'ok', 'latency' => '12ms'],
            ['name' => 'Redis Cache', 'status' => 'ok', 'latency' => '3ms'],
            ['name' => 'API Gateway', 'status' => 'ok', 'latency' => '45ms'],
            ['name' => 'خدمة الشحن', 'status' => 'ok', 'latency' => '120ms'],
            ['name' => 'خدمة الدفع', 'status' => 'ok', 'latency' => '89ms'],
            ['name' => 'التخزين', 'status' => 'ok', 'latency' => '15ms'],
        ] as $service)
            @php $isOk = $service['status'] === 'ok'; @endphp
            <div style="padding:16px;background:var(--sf);border-radius:12px;border:1px solid var(--bd)">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span style="font-weight:600;font-size:13px;color:var(--tx)">{{ $service['name'] }}</span>
                    <span style="width:10px;height:10px;border-radius:50%;background:{{ $isOk ? 'var(--ac)' : 'var(--dg)' }}"></span>
                </div>
                <div style="font-size:12px;color:var(--td)">
                    الحالة: <span style="color:{{ $isOk ? 'var(--ac)' : 'var(--dg)' }}">{{ $isOk ? 'متصل' : 'غير متصل' }}</span>
                    &nbsp;•&nbsp; {{ $service['latency'] }}
                </div>
            </div>
        @endforeach
    </div>
</x-card>

{{-- Recent Activity --}}
<x-card title="📋 آخر النشاطات">
    <div style="display:flex;flex-direction:column">
        @forelse($recentActivity ?? [] as $act)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--bd)">
                <div style="width:36px;height:36px;border-radius:8px;background:rgba(124,58,237,0.1);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">
                    {{ $act->icon ?? '📝' }}
                </div>
                <div style="flex:1">
                    <span style="font-size:13px;color:var(--tx)">{{ $act->description }}</span>
                    <span style="font-size:11px;color:var(--td);display:block;margin-top:2px">{{ $act->user->name ?? '—' }} • {{ $act->created_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <div class="empty-state">لا توجد نشاطات حديثة</div>
        @endforelse
    </div>
</x-card>
@endsection
