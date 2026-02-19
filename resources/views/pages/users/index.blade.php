@extends('layouts.app')
@section('title', 'المستخدمين')
@section('content')
<x-page-header title="المستخدمين" :subtitle="$users->total() . ' مستخدم'">
    <button class="btn btn-pr" data-modal-open="create-user">+ إضافة</button>
</x-page-header>
<div class="table-wrap"><table>
    <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th>إجراء</th></tr></thead>
    <tbody>
        @foreach($users as $u)
            <tr>
                <td><div style="display:flex;align-items:center;gap:8px"><div class="user-avatar">{{ mb_substr($u->name, 0, 1) }}</div><span style="font-weight:600">{{ $u->name }}</span></div></td>
                <td>{{ $u->email }}</td>
                <td><span class="badge badge-pp">{{ $u->roles->first()?->name ?? '—' }}</span></td>
                <td><x-badge :status="$u->status ?? 'active'" /></td>
                <td>{{ $u->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                <td class="td-actions">
                    <form action="{{ route('users.toggle', $u) }}" method="POST">@csrf @method('PATCH')
                        <button class="btn {{ ($u->status ?? 'active') === 'active' ? 'btn-wn' : 'btn-ac' }}">{{ ($u->status ?? 'active') === 'active' ? 'تعطيل' : 'تفعيل' }}</button>
                    </form>
                    <form action="{{ route('users.destroy', $u) }}" method="POST" data-confirm="حذف المستخدم؟">@csrf @method('DELETE') <button class="btn btn-dg">🗑</button></form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table></div>
<div style="margin-top:14px">{{ $users->links() }}</div>

<x-modal id="create-user" title="إضافة مستخدم">
    <form method="POST" action="{{ route('users.store') }}">@csrf
        <div class="form-grid">
            <div class="form-group"><label class="form-label">الاسم *</label><input name="name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">البريد *</label><input name="email" type="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">كلمة المرور *</label><input name="password" type="password" class="form-control" required></div>
            <div class="form-group"><label class="form-label">الدور</label>
                <select name="role" class="form-control">@foreach($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select>
            </div>
        </div>
        <button type="submit" class="btn btn-pr" style="margin-top:10px">إضافة</button>
    </form>
</x-modal>
@endsection
