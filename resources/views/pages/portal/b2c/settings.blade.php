@extends('layouts.app')
@section('title', 'بوابة الأفراد | الإعدادات')

@section('content')
<div style="margin-bottom:24px">
    <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
        <a href="{{ route('b2c.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأفراد</a>
        <span style="margin:0 6px">/</span>
        <span>الإعدادات</span>
    </div>
    <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">إعدادات الحساب</h1>
    <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:720px">
        عدّل بياناتك الشخصية وإعدادات حسابك الفردي.
    </p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px">
    {{-- تعديل البيانات --}}
    <x-card title="البيانات الشخصية">
        <form action="{{ route('b2c.settings.update') }}" method="POST">
            @csrf @method('PUT')
            <div style="margin-bottom:14px">
                <label class="form-label">الاسم الكامل <span style="color:var(--dg)">*</span></label>
                <input type="text" name="name" class="form-input" required value="{{ old('name', $user->name) }}">
                @error('name')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
            </div>
            <div style="margin-bottom:14px">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->phone ?? '') }}" placeholder="+966">
            </div>
            <div style="margin-bottom:20px">
                <label class="form-label">اللغة المفضلة</label>
                <select name="locale" class="form-input">
                    <option value="ar" {{ ($user->locale ?? 'ar') === 'ar' ? 'selected' : '' }}>العربية</option>
                    <option value="en" {{ ($user->locale ?? '') === 'en' ? 'selected' : '' }}>English</option>
                </select>
            </div>
            <button type="submit" class="btn btn-pr">حفظ التغييرات</button>
        </form>
    </x-card>

    {{-- معلومات الحساب --}}
    <x-card title="معلومات الحساب">
        <table style="width:100%;font-size:14px;border-collapse:collapse;margin-bottom:20px">
            <tr><td style="padding:8px 0;color:var(--td)">البريد الإلكتروني</td><td style="padding:8px 0;font-weight:600">{{ $user->email }}</td></tr>
            <tr><td style="padding:8px 0;color:var(--td)">نوع الحساب</td><td style="padding:8px 0">{{ $account->type === 'individual' ? 'حساب فردي' : $account->type }}</td></tr>
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
            <tr><td style="padding:8px 0;color:var(--td)">آخر دخول</td><td style="padding:8px 0">{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
        </table>
        <div style="display:flex;flex-direction:column;gap:8px">
            <a href="{{ route('b2c.addresses.index') }}" class="btn btn-s">إدارة العناوين</a>
            <a href="{{ route('b2c.support.index') }}" class="btn btn-ghost">الدعم الفني</a>
        </div>
    </x-card>
</div>
@endsection
