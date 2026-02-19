@extends('layouts.app')
@section('title', 'المتاجر')
@section('content')
<x-page-header title="المتاجر">
    <button class="btn btn-pr" data-modal-open="create-store">+ ربط متجر</button>
</x-page-header>
<div class="grid-3">
    @foreach($stores as $s)
        <div class="entity-card">
            <div class="top">
                <div>
                    <h3>{{ $s->name }}</h3>
                    <p class="meta">{{ $s->platform }} — {{ $s->url }}</p>
                </div>
                <x-badge :status="$s->status" />
            </div>
            <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--tm);margin-bottom:10px">
                <span>{{ $s->orders_count ?? 0 }} طلب</span>
                <span>مزامنة: {{ $s->last_synced_at?->diffForHumans() ?? '—' }}</span>
            </div>
            <div class="card-actions">
                <form action="{{ route('stores.sync', $s) }}" method="POST">@csrf <button class="btn btn-s">🔄 مزامنة</button></form>
                <form action="{{ route('stores.test', $s) }}" method="POST">@csrf <button class="btn btn-pp">⚡ اختبار</button></form>
                <form action="{{ route('stores.destroy', $s) }}" method="POST" data-confirm="حذف المتجر؟">@csrf @method('DELETE') <button class="btn btn-dg">🗑</button></form>
            </div>
        </div>
    @endforeach
</div>

<x-modal id="create-store" title="ربط متجر جديد">
    <form method="POST" action="{{ route('stores.store') }}">@csrf
        <div class="form-group"><label class="form-label">اسم المتجر *</label><input name="name" class="form-control" required></div>
        <div class="form-group"><label class="form-label">المنصة</label>
            <select name="platform" class="form-control"><option>Shopify</option><option>سلة</option><option>زد</option><option>WooCommerce</option></select>
        </div>
        <div class="form-group"><label class="form-label">رابط المتجر *</label><input name="url" class="form-control" required></div>
        <button type="submit" class="btn btn-pr" style="margin-top:10px">ربط</button>
    </form>
</x-modal>
@endsection
