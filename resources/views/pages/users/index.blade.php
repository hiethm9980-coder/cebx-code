@extends('layouts.app')
@section('title', 'إدارة المستخدمين')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">👥 إدارة المستخدمين</h1>
    <button class="btn btn-pr" data-modal-open="invite-user">+ دعوة مستخدم</button>
</div>

<div class="stats-grid" style="margin-bottom:24px">
    <x-stat-card icon="👥" label="إجمالي المستخدمين" :value="$users->total()" />
    <x-stat-card icon="✅" label="نشط" :value="$activeCount ?? 0" />
    <x-stat-card icon="⏸️" label="معلق" :value="$pendingCount ?? 0" />
    <x-stat-card icon="🚫" label="معطل" :value="$disabledCount ?? 0" />
</div>

<x-card>
    <div class="table-wrap">
        <table>
            <thead><tr><th>المستخدم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th></th></tr></thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $roleColors = ['مدير' => 'badge-pr', 'مشرف' => 'badge-pp', 'مشغّل' => 'badge-ac', 'مُطلع' => 'badge-td'];
                        $roleBadge = $roleColors[$user->role_name ?? ''] ?? 'badge-td';
                        $initial = mb_substr($user->name, 0, 1);
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="user-avatar" style="background:{{ $roleBadge === 'badge-pr' ? 'rgba(59,130,246,0.2)' : 'rgba(139,92,246,0.2)' }};color:{{ $roleBadge === 'badge-pr' ? 'var(--pr)' : 'var(--pp)' }}">{{ $initial }}</div>
                                <span style="color:var(--tx)">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge {{ $roleBadge }}">{{ $user->role_name ?? '—' }}</span></td>
                        <td><span style="color:{{ $user->is_active ? 'var(--ac)' : 'var(--dg)' }}">● {{ $user->is_active ? 'نشط' : 'معطل' }}</span></td>
                        <td>{{ $user->last_login_at?->diffForHumans() ?? '—' }}</td>
                        <td><a href="{{ route('users.edit', $user) }}" class="btn btn-s">تعديل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">لا يوجد مستخدمون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $users->links() }}</div>
</x-card>

<x-modal id="invite-user" title="دعوة مستخدم جديد">
    <form method="POST" action="{{ route('invitations.store') }}">
        @csrf
        <div style="margin-bottom:16px"><label class="form-label">الاسم الكامل</label><input type="text" name="name" placeholder="اسم المستخدم" class="form-input"></div>
        <div style="margin-bottom:16px"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" placeholder="user@company.sa" class="form-input" required></div>
        <div style="margin-bottom:16px"><label class="form-label">المسمى الوظيفي</label><input type="text" name="job_title" placeholder="مثال: مسؤول الشحن" class="form-input"></div>
        <div style="margin-bottom:16px">
            <label class="form-label">الدور</label>
            <select name="role_name" class="form-input">
                <option>مدير</option><option>مشرف</option><option>مشغّل</option><option>مُطلع</option>
            </select>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">إرسال الدعوة</button>
        </div>
    </form>
</x-modal>
@endsection
