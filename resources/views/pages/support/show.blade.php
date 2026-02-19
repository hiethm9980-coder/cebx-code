@extends('layouts.app')
@section('title', 'تذكرة ' . $ticket->ticket_number)
@section('content')
<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
    <a href="{{ route('support.index') }}" class="btn btn-s">→ رجوع</a>
    <div>
        <h1 style="color:var(--tx);font-size:18px;font-weight:700">{{ $ticket->subject }}</h1>
        <p style="color:var(--tm);font-size:11px">{{ $ticket->ticket_number }} | {{ $ticket->created_at->format('Y-m-d') }} | معيّن: {{ $ticket->assignee?->name ?? '—' }}</p>
    </div>
</div>

@if($ticket->status !== 'resolved')
    <div style="margin-bottom:14px">
        <form action="{{ route('support.resolve', $ticket) }}" method="POST" style="display:inline">@csrf @method('PATCH')
            <button class="btn btn-ac">✓ حل التذكرة</button>
        </form>
    </div>
@endif

<x-card title="المحادثة">
    <div style="min-height:200px;margin-bottom:14px">
        @forelse($ticket->replies as $msg)
            <div class="chat-msg {{ $msg->is_customer ? 'user' : 'agent' }}">
                <div class="chat-bubble">
                    <p class="chat-text">{{ $msg->message }}</p>
                    <p class="chat-meta">{{ $msg->user?->name ?? 'فريق الدعم' }} — {{ $msg->created_at->format('H:i') }}</p>
                </div>
            </div>
        @empty
            <div class="empty-state">لا توجد رسائل بعد</div>
        @endforelse
    </div>

    <form method="POST" action="{{ route('support.reply', $ticket) }}" class="chat-input">
        @csrf
        <input name="message" class="form-control" placeholder="اكتب رداً..." required>
        <button type="submit" class="btn btn-pr">إرسال</button>
    </form>
</x-card>
@endsection
