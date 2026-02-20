@extends('layouts.app')
@section('title', 'دفتر العناوين')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="font-size:24px;font-weight:700;color:var(--tx);margin:0">📒 دفتر العناوين</h1>
    <button class="btn btn-pr" data-modal-open="add-address" @if($portalType === 'b2c') style="background:#0D9488" @endif>+ عنوان جديد</button>
</div>

<div class="grid-2">
    @forelse($addresses ?? [] as $address)
        <x-card>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                <div style="display:flex;gap:12px;align-items:center">
                    <div style="width:44px;height:44px;border-radius:12px;background:{{ $address->is_default_sender ? 'rgba(13,148,136,0.13)' : 'var(--sf)' }};display:flex;align-items:center;justify-content:center;font-size:20px">
                        {{ $address->type === 'sender' ? '📤' : ($address->type === 'recipient' ? '📥' : '📍') }}
                    </div>
                    <div>
                        <div style="font-weight:600;color:var(--tx)">{{ $address->label ?? $address->contact_name }}</div>
                        <div style="font-size:12px;color:var(--td)">{{ ucfirst($address->type) }}</div>
                    </div>
                </div>
                @if($address->is_default_sender)
                    <span class="badge badge-ac">افتراضي</span>
                @endif
            </div>
            <div style="font-size:13px;color:var(--tm);line-height:2;margin-bottom:12px">
                {{ $address->contact_name }}<br>
                📞 {{ $address->phone }}<br>
                📍 {{ $address->city }}، {{ $address->address_line_1 }}
            </div>
            <div style="display:flex;gap:8px">
                @if(!$address->is_default_sender)
                    <form method="POST" action="{{ route('addresses.default', $address) }}">@csrf @method('PATCH')
                        <button type="submit" class="btn btn-s">⭐ تعيين افتراضي</button>
                    </form>
                @endif
                <button class="btn btn-s">✏️ تعديل</button>
                <form method="POST" action="{{ route('addresses.destroy', $address) }}">@csrf @method('DELETE')
                    <button type="submit" class="btn btn-dg btn-sm" onclick="return confirm('حذف العنوان؟')">🗑️</button>
                </form>
            </div>
        </x-card>
    @empty
        <div class="empty-state" style="grid-column:1/3">لا توجد عناوين محفوظة</div>
    @endforelse
</div>

<x-modal id="add-address" title="إضافة عنوان جديد">
    <form method="POST" action="{{ route('addresses.store') }}">
        @csrf
        <div style="margin-bottom:16px"><label class="form-label">العنوان المحفوظ</label><input type="text" name="label" placeholder="مثال: المنزل، العمل..." class="form-input"></div>
        <div style="margin-bottom:16px"><label class="form-label">الاسم</label><input type="text" name="contact_name" placeholder="الاسم الكامل" class="form-input" required></div>
        <div style="margin-bottom:16px"><label class="form-label">الهاتف</label><input type="text" name="phone" placeholder="05xxxxxxxx" class="form-input" required></div>
        <div class="grid-2">
            <div style="margin-bottom:16px">
                <label class="form-label">الدولة</label>
                <select name="country" class="form-input">
                    <option value="SA">🇸🇦 السعودية</option><option value="AE">🇦🇪 الإمارات</option><option value="KW">🇰🇼 الكويت</option>
                </select>
            </div>
            <div style="margin-bottom:16px"><label class="form-label">المدينة</label><input type="text" name="city" placeholder="المدينة" class="form-input" required></div>
        </div>
        <div style="margin-bottom:16px"><label class="form-label">العنوان التفصيلي</label><input type="text" name="address_line_1" placeholder="الحي، الشارع، رقم المبنى" class="form-input" required></div>
        <label style="display:flex;align-items:center;gap:8px;color:var(--tm);font-size:13px;cursor:pointer;margin-bottom:16px">
            <input type="checkbox" name="is_default_sender"> تعيين كعنوان افتراضي
        </label>
        <div style="display:flex;justify-content:flex-end;gap:10px">
            <button type="button" class="btn btn-s" data-modal-close>إلغاء</button>
            <button type="submit" class="btn btn-pr" @if($portalType === 'b2c') style="background:#0D9488" @endif>حفظ العنوان</button>
        </div>
    </form>
</x-modal>
@endsection
