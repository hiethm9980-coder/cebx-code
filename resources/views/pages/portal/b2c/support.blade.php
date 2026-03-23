@extends('layouts.app')
@section('title', 'بوابة الأفراد | الدعم')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('b2c.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأفراد</a>
            <span style="margin:0 6px">/</span>
            <span>الدعم</span>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">مركز الدعم</h1>
        <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:720px">
            أنشئ طلبات دعم وتتبع حالتها. فريق الدعم سيتواصل معك عبر البريد الإلكتروني.
        </p>
    </div>
    <button class="btn btn-pr" onclick="document.getElementById('modal-support').style.display='flex'">طلب دعم جديد</button>
</div>

<x-card title="طلبات الدعم">
    <div style="overflow:auto">
        <table class="table">
            <thead>
            <tr>
                <th>الموضوع</th>
                <th>الحالة</th>
                <th>تاريخ الإنشاء</th>
                <th>آخر تحديث</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tickets as $ticket)
                <tr>
                    <td style="font-weight:600">{{ $ticket->subject ?? $ticket->title ?? '—' }}</td>
                    <td>
                        @php
                            $statusClass = match($ticket->status ?? '') {
                                'open' => 'badge-wn',
                                'resolved', 'closed' => 'badge-ac',
                                'in_progress' => 'badge-pp',
                                default => 'badge-wn',
                            };
                            $statusLabel = match($ticket->status ?? '') {
                                'open' => 'مفتوح',
                                'in_progress' => 'قيد المعالجة',
                                'resolved' => 'محلول',
                                'closed' => 'مغلق',
                                default => $ticket->status ?? '—',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ optional($ticket->created_at)->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ optional($ticket->updated_at)->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty-state">لا توجد طلبات دعم. أنشئ طلبًا إذا واجهت مشكلة.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{-- Modal: طلب دعم --}}
<div id="modal-support" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:500px;position:relative">
        <button onclick="document.getElementById('modal-support').style.display='none'"
                style="position:absolute;top:16px;left:16px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--td)">✕</button>
        <h2 style="font-size:20px;font-weight:800;color:var(--tx);margin:0 0 20px">طلب دعم جديد</h2>
        <form action="{{ route('b2c.support.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:14px">
                <label class="form-label">الموضوع <span style="color:var(--dg)">*</span></label>
                <input type="text" name="subject" class="form-input" required placeholder="وصف مختصر للمشكلة">
                @error('subject')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:24px">
                <label class="form-label">التفاصيل <span style="color:var(--dg)">*</span></label>
                <textarea name="body" class="form-input" required rows="5" placeholder="اشرح المشكلة بالتفصيل..."></textarea>
                @error('body')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-pr" style="width:100%">إرسال الطلب</button>
        </form>
    </div>
</div>
@endsection
