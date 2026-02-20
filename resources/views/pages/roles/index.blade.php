@extends('layouts.app')
@section('title', 'الأدوار والصلاحيات')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">🔐 الأدوار والصلاحيات</h1>
    <button class="btn btn-pr" data-modal-open="create-role">+ إنشاء دور</button>
</div>

{{-- ═══ ROLE CARDS ═══ --}}
<div class="grid-4" style="margin-bottom:24px">
    @php
        $roleConfig = [
            ['name' => 'مدير', 'icon' => '👑', 'desc' => 'صلاحيات كاملة', 'color' => '#3B82F6'],
            ['name' => 'مشرف', 'icon' => '⭐', 'desc' => 'إدارة الشحنات والطلبات', 'color' => '#8B5CF6'],
            ['name' => 'مشغّل', 'icon' => '⚙️', 'desc' => 'إنشاء ومتابعة الشحنات', 'color' => '#10B981'],
            ['name' => 'مُطلع', 'icon' => '👁️', 'desc' => 'عرض فقط', 'color' => '#64748B'],
        ];
    @endphp
    @foreach($roles ?? $roleConfig as $i => $role)
        @php $rc = $roleConfig[$i] ?? $roleConfig[0]; @endphp
        <div class="entity-card" style="border-top:3px solid {{ $rc['color'] }}">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <span style="font-size:28px">{{ $rc['icon'] }}</span>
                <span style="background:{{ $rc['color'] }}22;color:{{ $rc['color'] }};padding:3px 10px;border-radius:12px;font-size:12px">
                    {{ is_array($role) ? ($role['users_count'] ?? 0) : ($role->users_count ?? 0) }} مستخدم
                </span>
            </div>
            <div style="font-weight:700;color:var(--tx);font-size:16px;margin-bottom:4px">{{ is_array($role) ? $role['name'] : $role->name }}</div>
            <div style="font-size:12px;color:var(--td)">{{ $rc['desc'] }}</div>
        </div>
    @endforeach
</div>

{{-- ═══ PERMISSIONS MATRIX ═══ --}}
<x-card title="مصفوفة الصلاحيات">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:right">الصلاحية</th>
                    <th style="text-align:center">👑 مدير</th>
                    <th style="text-align:center">⭐ مشرف</th>
                    <th style="text-align:center">⚙️ مشغّل</th>
                    <th style="text-align:center">👁️ مُطلع</th>
                </tr>
            </thead>
            <tbody>
                @foreach([
                    ['عرض الشحنات', [1,1,1,1]],
                    ['إنشاء شحنة', [1,1,1,0]],
                    ['إلغاء شحنة', [1,1,0,0]],
                    ['إدارة الطلبات', [1,1,1,0]],
                    ['ربط المتاجر', [1,1,0,0]],
                    ['عرض المحفظة', [1,1,1,1]],
                    ['شحن الرصيد', [1,1,0,0]],
                    ['عرض التقارير', [1,1,1,1]],
                    ['إدارة المستخدمين', [1,0,0,0]],
                    ['إدارة الأدوار', [1,0,0,0]],
                    ['إعدادات المنظمة', [1,0,0,0]],
                ] as $perm)
                    <tr>
                        <td style="font-size:13px;color:var(--tx)">{{ $perm[0] }}</td>
                        @foreach($perm[1] as $val)
                            <td style="text-align:center">
                                @if($val)
                                    <span style="color:var(--ac);font-size:18px">✓</span>
                                @else
                                    <span style="color:var(--bd);font-size:18px">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-card>

<x-modal id="create-role" title="إنشاء دور جديد">
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        <div style="margin-bottom:16px"><label class="form-label">اسم الدور</label><input type="text" name="name" placeholder="مثال: محاسب" class="form-input" required></div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr">إنشاء</button>
        </div>
    </form>
</x-modal>
@endsection
