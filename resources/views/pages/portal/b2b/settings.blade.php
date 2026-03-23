@extends('layouts.app')
@section('title', 'بوابة الأعمال | الإعدادات')

@section('content')
<div style="margin-bottom:24px">
    <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
        <a href="{{ route('b2b.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأعمال</a>
        <span style="margin:0 6px">/</span>
        <span>الإعدادات</span>
    </div>
    <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">إعدادات الحساب</h1>
    <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:760px">
        إدارة بيانات حساب المنظمة ومعلومات المستخدم الشخصية. لتغيير الإعدادات العميقة للمنصة، استخدم صفحة الإعدادات الكاملة.
    </p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px">
    {{-- معلومات المستخدم --}}
    <x-card title="معلومات المستخدم">
        <table style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:20px">
            <tr><td style="padding:8px 0;color:var(--td)">الاسم</td><td style="padding:8px 0;font-weight:600">{{ $user->name }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">البريد</td><td style="padding:8px 0">{{ $user->email }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">الحالة</td><td style="padding:8px 0">
                <span class="badge {{ ($user->status ?? 'active') === 'active' ? 'badge-ac' : 'badge-wn' }}">
                    {{ ($user->status ?? 'active') === 'active' ? 'نشط' : ($user->status ?? '—') }}
                </span>
            </td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">آخر دخول</td><td style="padding:8px 0">{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
        </table>
        <a href="{{ route('settings.index') }}" class="btn btn-pr">تعديل الإعدادات الكاملة</a>
    </x-card>

    {{-- معلومات المنظمة --}}
    <x-card title="معلومات المنظمة">
        <table style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:20px">
            <tr><td style="padding:8px 0;color:var(--td)">اسم المنظمة</td><td style="padding:8px 0;font-weight:600">{{ $account->name }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">النوع</td><td style="padding:8px 0">{{ $account->type === 'organization' ? 'منظمة' : $account->type }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">العملة</td><td style="padding:8px 0">{{ $account->currency ?? 'SAR' }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">حالة KYC</td><td style="padding:8px 0">
                <span class="badge {{ ($account->kyc_status ?? 'pending') === 'approved' ? 'badge-ac' : 'badge-wn' }}">
                    {{ match($account->kyc_status ?? 'pending') {
                        'approved' => 'موثق',
                        'pending' => 'قيد المراجعة',
                        'rejected' => 'مرفوض',
                        default => $account->kyc_status ?? '—',
                    } }}
                </span>
            </td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">تاريخ الإنشاء</td><td style="padding:8px 0">{{ optional($account->created_at)->format('Y-m-d') ?? '—' }}</td></tr>
        </table>
    </x-card>

    {{-- روابط الإعدادات --}}
    <x-card title="إعدادات سريعة">
        <div style="display:flex;flex-direction:column;gap:12px">
            @foreach([
                ['label' => 'إدارة المستخدمين', 'route' => 'b2b.users.index', 'desc' => 'عرض وإدارة أعضاء الفريق'],
                ['label' => 'إدارة الأدوار', 'route' => 'b2b.roles.index', 'desc' => 'مراجعة وتعديل الأدوار والصلاحيات'],
                ['label' => 'الدعوات', 'route' => 'b2b.invitations.index', 'desc' => 'دعوة أعضاء جدد للمنظمة'],
                ['label' => 'الإعدادات الكاملة', 'route' => 'settings.index', 'desc' => 'إعدادات المنصة التفصيلية'],
            ] as $link)
                <a href="{{ route($link['route']) }}" style="display:flex;justify-content:space-between;align-items:center;padding:14px;border:1px solid var(--bd);border-radius:14px;text-decoration:none;color:var(--tx);transition:background .2s"
                   onmouseover="this.style.background='var(--sf)'" onmouseout="this.style.background='transparent'">
                    <div>
                        <div style="font-weight:600">{{ $link['label'] }}</div>
                        <div style="font-size:12px;color:var(--tm);margin-top:2px">{{ $link['desc'] }}</div>
                    </div>
                    <span style="color:var(--tm)">←</span>
                </a>
            @endforeach
        </div>
    </x-card>
</div>
@endsection
