@extends('layouts.app')
@section('title', 'إدارة الدعوات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">📨 إدارة الدعوات</h1>
    <button class="btn btn-pr" data-modal-open="new-invitation">+ دعوة جديدة</button>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <x-stat-card icon="📨" label="إجمالي الدعوات" :value="$invitations->total()" />
    <x-stat-card icon="✅" label="مقبولة" :value="$acceptedCount ?? 0" />
    <x-stat-card icon="⏳" label="معلقة" :value="$pendingCount ?? 0" />
    <x-stat-card icon="❌" label="منتهية الصلاحية" :value="$expiredCount ?? 0" />
</div>

<x-card>
    <div class="table-wrap">
        <table>
            <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>تاريخ الإرسال</th><th>الصلاحية</th><th></th></tr></thead>
            <tbody>
                @forelse($invitations as $inv)
                    @php
                        $statusColors = [
                            'pending' => ['معلقة', 'badge-wn'],
                            'accepted' => ['مقبولة', 'badge-ac'],
                            'expired' => ['منتهية', 'badge-dg'],
                        ];
                        $sc = $statusColors[$inv->status] ?? [$inv->status, 'badge-td'];
                    @endphp
                    <tr>
                        <td>{{ $inv->name ?? '—' }}</td>
                        <td>{{ $inv->email }}</td>
                        <td><span class="badge badge-pp">{{ $inv->role_name ?? '—' }}</span></td>
                        <td><span class="badge {{ $sc[1] }}">{{ $sc[0] }}</span></td>
                        <td>{{ $inv->created_at->format('d/m/Y') }}</td>
                        <td>{{ $inv->expires_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:6px">
                                @if($inv->status === 'pending')
                                    <form method="POST" action="{{ route('invitations.store') }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="email" value="{{ $inv->email }}">
                                        <input type="hidden" name="role_name" value="{{ $inv->role_name }}">
                                        <button type="submit" class="btn btn-pr btn-sm">إعادة إرسال</button>
                                    </form>
                                    <button class="btn btn-dg btn-sm">إلغاء</button>
                                @elseif($inv->status === 'expired')
                                    <form method="POST" action="{{ route('invitations.store') }}" style="display:inline">
                                        @csrf
                                        <input type="hidden" name="email" value="{{ $inv->email }}">
                                        <button type="submit" class="btn btn-pr btn-sm">إعادة إرسال</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state">لا توجد دعوات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())
        <div style="margin-top:14px">{{ $invitations->links() }}</div>
    @endif
</x-card>

<x-modal id="new-invitation" title="دعوة مستخدم جديد">
    <form method="POST" action="{{ route('invitations.store') }}">
        @csrf
        <div style="margin-bottom:16px"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" placeholder="user@company.sa" class="form-input" required></div>
        <div style="margin-bottom:16px">
            <label class="form-label">الدور</label>
            <select name="role_name" class="form-input"><option>مشغّل</option><option>مشرف</option><option>مُطلع</option></select>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">إرسال الدعوة</button>
        </div>
    </form>
</x-modal>
@endsection
