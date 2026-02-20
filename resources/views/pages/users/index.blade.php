@extends('layouts.app')
@section('title', 'إدارة المستخدمين')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:800;color:var(--tx);margin:0">👥 إدارة المستخدمين</h1>
    <a href="{{ route('invitations.index') }}" class="btn btn-pr">+ دعوة مستخدم</a>
</div>

<x-card>
    <div class="table-wrap">
        <table>
            <thead><tr><th>المستخدم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th></th></tr></thead>
            <tbody>
                @forelse($users as $user)
                    @php
                        $roleColors = ['مدير' => '#3B82F6', 'مشرف' => '#8B5CF6', 'مشغّل' => '#10B981', 'مُطلع' => '#94A3B8'];
                        $rc = $roleColors[$user->role_name] ?? '#94A3B8';
                    @endphp
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="user-avatar" style="background:{{ $rc }}20;color:{{ $rc }}">{{ mb_substr($user->name, 0, 1) }}</div>
                                <span style="font-weight:600;font-size:13px">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td style="font-size:13px;color:var(--td)">{{ $user->email }}</td>
                        <td><span class="badge" style="background:{{ $rc }}15;color:{{ $rc }}">{{ $user->role_name }}</span></td>
                        <td><span style="color:{{ $user->is_active ? 'var(--ac)' : 'var(--dg)' }}">● {{ $user->is_active ? 'نشط' : 'معطّل' }}</span></td>
                        <td style="font-size:12px;color:var(--tm)">{{ $user->last_login_at?->diffForHumans() ?? 'لم يسجل دخول' }}</td>
                        <td><a href="{{ route('users.edit', $user) }}" class="btn btn-s" style="font-size:12px;padding:5px 14px">تعديل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">لا يوجد مستخدمون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div style="margin-top:14px">{{ $users->links() }}</div>
    @endif
</x-card>
@endsection
