@extends('layouts.app')
@section('title', 'تعديل الشركة')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">تعديل الشركة</h1>
    <a href="{{ route('companies.index') }}" class="btn btn-s">← العودة</a>
</div>

<x-card>
    <form method="POST" action="{{ route('companies.update') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $company->id }}">
        @if($errors->any())
            <div style="background:#FEE2E2;color:#B91C1C;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px">
                <ul style="margin:0;padding-right:20px">{{ implode('', $errors->all('<li>:message</li>')) }}</ul>
            </div>
        @endif
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div><label class="form-label">اسم الشركة</label><input type="text" name="name" class="form-input" value="{{ old('name', $company->name) }}" required></div>
            <div><label class="form-label">الكود</label><input type="text" name="code" class="form-input" value="{{ old('code', $company->code ?? '') }}" placeholder="اختياري"></div>
            <div><label class="form-label">النوع</label><select name="type" class="form-input"><option value="carrier" {{ old('type', $company->type ?? '') === 'carrier' ? 'selected' : '' }}>ناقل</option><option value="agent" {{ old('type', $company->type ?? '') === 'agent' ? 'selected' : '' }}>وكيل</option><option value="partner" {{ old('type', $company->type ?? '') === 'partner' ? 'selected' : '' }}>شريك</option></select></div>
            <div><label class="form-label">البلد</label><input type="text" name="country" class="form-input" value="{{ old('country', $company->country ?? '') }}" placeholder="SAU, YEM, EGY"></div>
            <div><label class="form-label">البريد الإلكتروني</label><input type="email" name="contact_email" class="form-input" value="{{ old('contact_email', $company->contact_email ?? $company->email ?? '') }}"></div>
            <div><label class="form-label">رقم الهاتف</label><input type="text" name="contact_phone" class="form-input" value="{{ old('contact_phone', $company->contact_phone ?? $company->phone ?? '') }}"></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
            <a href="{{ route('companies.index') }}" class="btn btn-s">إلغاء</a>
            <button type="submit" class="btn btn-pr">حفظ التعديلات</button>
        </div>
    </form>
</x-card>
@endsection
