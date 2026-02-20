@extends('layouts.app')
@section('title', 'الإشعارات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🔔 الإشعارات</h1>
    @if(($notifications ?? collect())->where('read_at', null)->count() > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button type="submit" class="btn btn-s">✓ تحديد الكل كمقروء</button>
        </form>
    @endif
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <x-stat-card icon="🔔" label="الكل" :value="$notifications->total() ?? 0" />
    <x-stat-card icon="🔵" label="غير مقروء" :value="$unreadCount ?? 0" />
    <x-stat-card icon="✅" label="مقروء" :value="$readCount ?? 0" />
</div>

{{-- Filters --}}
<x-card>
    <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        @php
            $filter = request('filter', 'all');
        @endphp
        <a href="{{ route('notifications.index') }}" class="btn {{ $filter === 'all' ? 'btn-pr' : 'btn-s' }}" style="font-size:13px">الكل</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="btn {{ $filter === 'unread' ? 'btn-pr' : 'btn-s' }}" style="font-size:13px">غير مقروء</a>
        <a href="{{ route('notifications.index', ['filter' => 'shipment']) }}" class="btn {{ $filter === 'shipment' ? 'btn-pr' : 'btn-s' }}" style="font-size:13px">📦 شحنات</a>
        <a href="{{ route('notifications.index', ['filter' => 'wallet']) }}" class="btn {{ $filter === 'wallet' ? 'btn-pr' : 'btn-s' }}" style="font-size:13px">💰 محفظة</a>
        <a href="{{ route('notifications.index', ['filter' => 'system']) }}" class="btn {{ $filter === 'system' ? 'btn-pr' : 'btn-s' }}" style="font-size:13px">⚙️ نظام</a>
    </div>

    <div style="display:flex;flex-direction:column">
        @forelse($notifications ?? [] as $notif)
            @php
                $isUnread = is_null($notif->read_at);
                $typeIcons = ['shipment' => '📦', 'wallet' => '💰', 'system' => '⚙️', 'support' => '🎧', 'user' => '👤'];
                $icon = $typeIcons[$notif->type] ?? '🔔';
            @endphp
            <div style="display:flex;align-items:flex-start;gap:14px;padding:16px 0;border-bottom:1px solid var(--bd);{{ $isUnread ? 'background:rgba(59,130,246,0.04);margin:0 -16px;padding-right:16px;padding-left:16px;border-radius:8px' : '' }}">
                <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;background:{{ $isUnread ? 'rgba(124,58,237,0.12)' : 'var(--sf)' }};flex-shrink:0">
                    {{ $icon }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
                        <span style="font-weight:{{ $isUnread ? '700' : '500' }};color:var(--tx);font-size:14px">{{ $notif->title }}</span>
                        @if($isUnread)
                            <span style="width:8px;height:8px;border-radius:50%;background:#7C3AED;flex-shrink:0"></span>
                        @endif
                    </div>
                    <p style="color:var(--td);font-size:13px;margin:4px 0 0;line-height:1.5">{{ $notif->body }}</p>
                    <span style="font-size:11px;color:var(--tm);margin-top:6px;display:block">{{ $notif->created_at->diffForHumans() }}</span>
                </div>
                @if($isUnread)
                    <form method="POST" action="{{ route('notifications.read', $notif) }}" style="flex-shrink:0">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-s" style="font-size:11px;padding:4px 10px">قراءة</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="empty-state" style="padding:40px 0">لا توجد إشعارات</div>
        @endforelse
    </div>

    @if(method_exists($notifications ?? collect(), 'links'))
        <div style="margin-top:14px">{{ $notifications->links() }}</div>
    @endif
</x-card>
@endsection
