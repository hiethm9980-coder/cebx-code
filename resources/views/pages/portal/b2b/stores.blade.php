@extends('layouts.app')
@section('title', 'بوابة الأعمال | المتاجر')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('b2b.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأعمال</a>
            <span style="margin:0 6px">/</span>
            <span>المتاجر</span>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">متاجر المنظمة</h1>
        <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:760px">
            اعرض المتاجر المربوطة بحساب المنظمة وحالة الاتصال بها. استخدم إدارة المتاجر الكاملة لمزامنة الطلبات أو إضافة متاجر جديدة.
        </p>
    </div>
    <a href="{{ route('stores.index') }}" class="btn btn-pr">فتح إدارة المتاجر</a>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    @foreach($stats as $stat)
        <x-stat-card :icon="$stat['icon']" :label="$stat['label']" :value="$stat['value']" />
    @endforeach
</div>

<x-card title="المتاجر المربوطة">
    <div style="overflow:auto">
        <table class="table">
            <thead>
            <tr>
                <th>اسم المتجر</th>
                <th>المنصة</th>
                <th>الحالة</th>
                <th>آخر مزامنة</th>
                <th>الإجراء</th>
            </tr>
            </thead>
            <tbody>
            @forelse($stores as $store)
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--tx)">{{ $store->name }}</div>
                        <div style="font-size:12px;color:var(--tm)">{{ $store->store_url ?? '' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-pp">{{ ucfirst($store->platform ?? '—') }}</span>
                    </td>
                    <td>
                        @php
                            $statusClass = match($store->status ?? '') {
                                'active' => 'badge-ac',
                                'error', 'failed' => 'badge-dg',
                                default => 'badge-wn',
                            };
                            $statusLabel = match($store->status ?? '') {
                                'active' => 'متصل',
                                'error', 'failed' => 'خطأ',
                                'inactive' => 'معطل',
                                default => $store->status ?? 'غير معروف',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ optional($store->last_synced_at)->format('Y-m-d H:i') ?? '—' }}</td>
                    <td style="display:flex;gap:8px;flex-wrap:wrap">
                        <form action="{{ route('stores.sync', $store) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-s">مزامنة</button>
                        </form>
                        <form action="{{ route('stores.test', $store) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-ghost">اختبار</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        لا توجد متاجر مربوطة بعد.
                        <a href="{{ route('stores.index') }}" style="color:var(--pr)">افتح إدارة المتاجر</a> لإضافة متجرك الأول.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
