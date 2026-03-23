@extends('layouts.app')
@section('title', 'بوابة الأعمال | الدعوات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('b2b.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأعمال</a>
            <span style="margin:0 6px">/</span>
            <span>الدعوات</span>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">دعوات الفريق</h1>
        <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:760px">
            أرسل دعوات لأعضاء الفريق الجدد وراقب حالة قبولهم للانضمام لحساب المنظمة.
        </p>
    </div>
    <button class="btn btn-pr" onclick="document.getElementById('modal-invite').style.display='flex'">دعوة عضو جديد</button>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    @foreach($stats as $stat)
        <x-stat-card :icon="$stat['icon']" :label="$stat['label']" :value="$stat['value']" />
    @endforeach
</div>

<x-card title="سجل الدعوات">
    <div style="overflow:auto">
        <table class="table">
            <thead>
            <tr>
                <th>البريد الإلكتروني</th>
                <th>الدور</th>
                <th>الحالة</th>
                <th>تاريخ الإرسال</th>
                <th>تنتهي في</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invitations as $inv)
                <tr>
                    <td>{{ $inv->email }}</td>
                    <td>
                        <span class="badge badge-pp">{{ $inv->role?->name ?? $inv->role_name ?? '—' }}</span>
                    </td>
                    <td>
                        @php
                            $statusClass = match($inv->status ?? '') {
                                'accepted' => 'badge-ac',
                                'expired', 'cancelled' => 'badge-dg',
                                default => 'badge-wn',
                            };
                            $statusLabel = match($inv->status ?? '') {
                                'pending' => 'معلقة',
                                'accepted' => 'مقبولة',
                                'expired' => 'منتهية',
                                'cancelled' => 'ملغاة',
                                default => $inv->status ?? '—',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ optional($inv->created_at)->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ optional($inv->expires_at)->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty-state">لا توجد دعوات مرسلة بعد. أرسل أول دعوة لفريقك.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{-- Modal: دعوة جديدة --}}
<div id="modal-invite" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:460px;position:relative">
        <button onclick="document.getElementById('modal-invite').style.display='none'"
                style="position:absolute;top:16px;left:16px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--td)">✕</button>
        <h2 style="font-size:20px;font-weight:800;color:var(--tx);margin:0 0 20px">دعوة عضو جديد</h2>
        <form action="{{ route('b2b.invitations.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:16px">
                <label class="form-label">البريد الإلكتروني <span style="color:var(--dg)">*</span></label>
                <input type="email" name="email" class="form-input" required placeholder="example@company.com">
                @error('email')<div style="color:var(--dg);font-size:13px;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:24px">
                <label class="form-label">الدور</label>
                <select name="role_name" class="form-input">
                    <option value="">— اختر دورًا —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->display_name ?: $role->name }}</option>
                    @endforeach
                    <option value="عارض">عارض</option>
                </select>
            </div>
            <button type="submit" class="btn btn-pr" style="width:100%">إرسال الدعوة</button>
        </form>
    </div>
</div>
@endsection
