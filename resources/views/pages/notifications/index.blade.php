@extends('layouts.app')
@section('title', 'الإشعارات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">🔔 الإشعارات</h1>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}" style="display:inline">@csrf
            <button type="submit" class="btn btn-s">✓ تحديد الكل كمقروء</button>
        </form>
    @endif
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <x-stat-card icon="🔔" label="الكل" :value="$notifications->total() ?? 0" />
    <x-stat-card icon="🔵" label="غير مقروءة" :value="$unreadCount" />
    <x-stat-card icon="✅" label="مقروءة" :value="$readCount" />
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:18px">
    @foreach(['' => 'الكل', 'unread' => 'غير مقروءة', 'shipment' => 'الشحنات', 'wallet' => 'المحفظة', 'system' => 'النظام'] as $val => $label)
        <button type="submit" name="filter" value="{{ $val }}" class="btn {{ request('filter','') === $val ? 'btn-pr' : 'btn-s' }}" style="font-size:13px">{{ $label }}</button>
    @endforeach
</form>

<div style="display:flex;flex-direction:column;gap:8px">
    @forelse($notifications as $notif)
        @php
            $icons = ['shipment' => '📦', 'wallet' => '💰', 'system' => '⚙️'];
        @endphp
        <div class="card" style="opacity:{{ $notif->read_at ? '0.7' : '1' }}">
            <div class="card-body" style="display:flex;align-items:center;gap:14px">
                <span style="font-size:24px">{{ $icons[$notif->type] ?? '🔔' }}</span>
                <div style="flex:1">
                    <div style="font-weight:{{ $notif->read_at ? '500' : '700' }};font-size:14px;color:var(--tx)">{{ $notif->title }}</div>
                    <div style="font-size:13px;color:var(--td);margin-top:4px">{{ $notif->body }}</div>
                    <div style="font-size:11px;color:var(--tm);margin-top:6px">{{ $notif->created_at->diffForHumans() }}</div>
                </div>
                @if(!$notif->read_at)
                    <form method="POST" action="{{ route('notifications.read', $notif) }}">@csrf @method('PATCH')
                        <button type="submit" class="btn btn-s" style="font-size:12px;padding:4px 12px">✓ قراءة</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-state">لا توجد إشعارات</div>
    @endforelse
</div>

@if($notifications->hasPages())
    <div style="margin-top:14px">{{ $notifications->links() }}</div>
@endif
@endsection
