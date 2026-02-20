@extends('layouts.app')
@section('title', 'تذكرة #' . ($ticket->id ?? ''))

@section('content')
@php
    $ref = $ticket->reference_number ?? '#TKT-' . str_pad($ticket->id ?? 0, 4, '0', STR_PAD_LEFT);
    $stMap = ['open' => ['🟢 مفتوحة', 'badge-ac'], 'in_progress' => ['🔵 قيد المعالجة', 'badge-in'], 'waiting' => ['🟡 بانتظار الرد', 'badge-wn'], 'resolved' => ['✅ تم الحل', 'badge-pp'], 'closed' => ['⚫ مغلقة', 'badge-td']];
    $st = $stMap[$ticket->status ?? 'open'] ?? ['—', 'badge-td'];
    $priorityMap = ['low' => ['منخفضة', 'var(--ac)'], 'medium' => ['متوسطة', 'var(--wn)'], 'high' => ['عالية', '#EF4444'], 'urgent' => ['عاجلة', '#DC2626']];
    $pr = $priorityMap[$ticket->priority ?? 'medium'] ?? ['—', 'var(--td)'];
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div>
        <a href="{{ route('support.index') }}" style="font-size:13px;color:var(--td);text-decoration:none">← العودة للتذاكر</a>
        <h1 style="font-size:22px;font-weight:700;color:var(--tx);margin:8px 0 0">{{ $ref }} — {{ $ticket->subject ?? '' }}</h1>
    </div>
    <div style="display:flex;gap:10px">
        @if(!in_array($ticket->status ?? '', ['resolved', 'closed']))
            <form method="POST" action="{{ route('support.resolve', $ticket) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-s" style="color:var(--ac)">✅ حل التذكرة</button>
            </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:20px">
    {{-- Main: Messages Thread --}}
    <div>
        <x-card>
            <div style="display:flex;flex-direction:column;gap:16px;margin-bottom:20px">
                {{-- Original message --}}
                <div style="padding:16px;background:var(--sf);border-radius:12px;border:1px solid var(--bd)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:32px;height:32px;border-radius:8px;background:rgba(124,58,237,0.15);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#7C3AED">
                                {{ mb_substr($ticket->user->name ?? 'م', 0, 1) }}
                            </div>
                            <div>
                                <span style="font-weight:600;font-size:13px">{{ $ticket->user->name ?? 'المستخدم' }}</span>
                                <span style="font-size:11px;color:var(--td);margin-right:8px">{{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    <p style="font-size:14px;color:var(--tx);line-height:1.7;margin:0">{{ $ticket->body ?? $ticket->description ?? '' }}</p>
                    @if($ticket->attachments_count ?? 0)
                        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--bd);font-size:12px;color:var(--td)">
                            📎 {{ $ticket->attachments_count }} مرفق
                        </div>
                    @endif
                </div>

                {{-- Replies --}}
                @foreach($ticket->replies ?? [] as $reply)
                    @php $isAgent = $reply->is_agent ?? false; @endphp
                    <div style="padding:16px;border-radius:12px;border:1px solid var(--bd);{{ $isAgent ? 'background:rgba(124,58,237,0.04);border-color:rgba(124,58,237,0.2)' : 'background:var(--sf)' }}">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;{{ $isAgent ? 'background:rgba(124,58,237,0.2);color:#7C3AED' : 'background:rgba(16,185,129,0.15);color:#10B981' }}">
                                    {{ mb_substr($reply->user->name ?? 'م', 0, 1) }}
                                </div>
                                <div>
                                    <span style="font-weight:600;font-size:13px">{{ $reply->user->name ?? '—' }}</span>
                                    @if($isAgent)
                                        <span class="badge badge-pp" style="font-size:10px;margin-right:6px">فريق الدعم</span>
                                    @endif
                                    <span style="font-size:11px;color:var(--td);display:block">{{ $reply->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        <p style="font-size:14px;color:var(--tx);line-height:1.7;margin:0">{{ $reply->body }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Reply Form --}}
            @if(!in_array($ticket->status ?? '', ['closed']))
                <form method="POST" action="{{ route('support.reply', $ticket) }}" style="border-top:1px solid var(--bd);padding-top:16px">
                    @csrf
                    <label class="form-label">إضافة رد</label>
                    <textarea name="body" class="form-input" rows="4" placeholder="اكتب ردك هنا..." required style="margin-bottom:12px"></textarea>
                    <div style="display:flex;justify-content:flex-end;gap:10px">
                        <button type="submit" class="btn btn-pr">إرسال الرد</button>
                    </div>
                </form>
            @endif
        </x-card>
    </div>

    {{-- Sidebar: Ticket Details --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <x-card title="📋 تفاصيل التذكرة">
            <x-info-row label="الحالة" :value="$st[0]" />
            <x-info-row label="الأولوية" :value="$pr[0]" />
            <x-info-row label="الفئة" :value="$ticket->category ?? 'عام'" />
            <x-info-row label="تاريخ الإنشاء" :value="$ticket->created_at->format('Y-m-d H:i') ?? '—'" />
            <x-info-row label="آخر تحديث" :value="$ticket->updated_at->diffForHumans() ?? '—'" />
            @if($ticket->assigned_to ?? null)
                <x-info-row label="المسؤول" :value="$ticket->assignee->name ?? '—'" />
            @endif
        </x-card>

        @if($ticket->shipment ?? null)
            <x-card title="📦 الشحنة المرتبطة">
                <x-info-row label="رقم التتبع" :value="$ticket->shipment->reference_number ?? '—'" />
                <x-info-row label="الناقل" :value="$ticket->shipment->carrier_code ?? '—'" />
                <x-info-row label="الحالة" :value="$ticket->shipment->status ?? '—'" />
                <a href="{{ route('shipments.show', $ticket->shipment_id) }}" class="btn btn-s" style="width:100%;text-align:center;margin-top:10px">عرض الشحنة</a>
            </x-card>
        @endif
    </div>
</div>
@endsection
