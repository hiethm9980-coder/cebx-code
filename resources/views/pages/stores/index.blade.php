@extends('layouts.app')
@section('title', 'إدارة المتاجر')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🏪 إدارة المتاجر</h1>
    <button class="btn btn-pr" data-modal-open="connect-store">+ ربط متجر جديد</button>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <x-stat-card icon="🏪" label="المتاجر المربوطة" :value="$stores->count()" />
    <x-stat-card icon="🛒" label="طلبات اليوم" :value="$todayOrders ?? 0" />
    <x-stat-card icon="📦" label="بانتظار الشحن" :value="$pendingOrders ?? 0" />
    <x-stat-card icon="🔄" label="آخر مزامنة" value="5 د" />
</div>

<div class="grid-2">
    @forelse($stores as $store)
        @php
            $platformIcons = ['salla' => '🟣', 'zid' => '🔵', 'shopify' => '🟢', 'woocommerce' => '🟠'];
            $icon = $platformIcons[$store->platform] ?? '📦';
        @endphp
        <x-card>
            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                <div style="display:flex;gap:14px;align-items:center">
                    <div style="width:48px;height:48px;border-radius:12px;background:var(--bd);display:flex;align-items:center;justify-content:center;font-size:24px">{{ $icon }}</div>
                    <div>
                        <div style="font-weight:600;color:var(--tx);font-size:15px">{{ $store->name }}</div>
                        <div style="font-size:12px;color:var(--td)">{{ $store->platform }}</div>
                    </div>
                </div>
                <x-badge :status="$store->status === 'active' ? 'connected' : 'disconnected'" />
            </div>
            <div class="grid-2" style="margin:16px 0;padding:16px;background:var(--sf);border-radius:8px">
                <div>
                    <div style="font-size:11px;color:var(--td)">إجمالي الطلبات</div>
                    <div style="font-size:18px;font-weight:700;color:var(--tx);font-family:monospace">{{ $store->orders_count ?? 0 }}</div>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--td)">آخر مزامنة</div>
                    <div style="font-size:13px;color:var(--tm);margin-top:4px">{{ $store->last_sync_at?->diffForHumans() ?? '—' }}</div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <form method="POST" action="{{ route('stores.sync', $store) }}"><@csrf>
                    <button type="submit" class="btn btn-pr btn-sm">🔄 مزامنة</button>
                </form>
                <a href="{{ route('stores.edit', $store) }}" class="btn btn-s">⚙️ إعدادات</a>
                <form method="POST" action="{{ route('stores.disconnect', $store) }}">@csrf @method('DELETE')
                    <button type="submit" class="btn btn-dg btn-sm" onclick="return confirm('هل أنت متأكد؟')">فصل</button>
                </form>
            </div>
        </x-card>
    @empty
        <div class="empty-state" style="grid-column:1/3">لا توجد متاجر مربوطة. اضغط "ربط متجر جديد" للبدء.</div>
    @endforelse
</div>

{{-- ═══ CONNECT STORE MODAL ═══ --}}
<x-modal id="connect-store" title="ربط متجر جديد" wide>
    <form method="POST" action="{{ route('stores.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
            @foreach([['🟣', 'salla', 'Salla'], ['🔵', 'zid', 'Zid'], ['🟢', 'shopify', 'Shopify'], ['🟠', 'woocommerce', 'WooCommerce']] as $p)
                <label style="padding:20px;background:var(--sf);border:1px solid var(--bd);border-radius:12px;text-align:center;cursor:pointer">
                    <input type="radio" name="platform" value="{{ $p[1] }}" style="display:none">
                    <div style="font-size:32px;margin-bottom:8px">{{ $p[0] }}</div>
                    <div style="font-weight:600;color:var(--tx);font-size:14px">{{ $p[2] }}</div>
                </label>
            @endforeach
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">رابط المتجر</label>
            <input type="url" name="store_url" placeholder="https://your-store.salla.sa" class="form-input">
        </div>
        <div style="margin-bottom:16px">
            <label class="form-label">مفتاح API</label>
            <input type="text" name="api_key" placeholder="sk_live_xxxxxxxx" class="form-input">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">ربط المتجر</button>
        </div>
    </form>
</x-modal>
@endsection
