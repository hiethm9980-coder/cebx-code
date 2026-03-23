@extends('layouts.app')
@section('title', 'بوابة الأفراد | العناوين')

@section('content')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;margin-bottom:24px">
    <div>
        <div style="font-size:12px;color:var(--tm);margin-bottom:8px">
            <a href="{{ route('b2c.dashboard') }}" style="color:inherit;text-decoration:none">بوابة الأفراد</a>
            <span style="margin:0 6px">/</span>
            <span>العناوين</span>
        </div>
        <h1 style="font-size:28px;font-weight:800;color:var(--tx);margin:0">عناوين الشحن</h1>
        <p style="color:var(--td);font-size:14px;margin:8px 0 0;max-width:720px">
            أضف عناوينك المتكررة لتسريع إنشاء الشحنات. يمكنك تعيين عنوان افتراضي يتم اختياره تلقائيًا عند إنشاء طلب جديد.
        </p>
    </div>
    <button class="btn btn-pr" onclick="document.getElementById('modal-address').style.display='flex'">إضافة عنوان جديد</button>
</div>

@if($addresses->isEmpty())
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:20px;border:1px solid var(--bd)">
        <div style="font-size:48px;margin-bottom:16px">📍</div>
        <div style="font-weight:700;font-size:18px;color:var(--tx);margin-bottom:8px">لا توجد عناوين محفوظة</div>
        <p style="color:var(--td);max-width:400px;margin:0 auto 20px">أضف عناوين شحنك المتكررة لتوفير الوقت عند إنشاء الشحنات.</p>
        <button class="btn btn-pr" onclick="document.getElementById('modal-address').style.display='flex'">إضافة أول عنوان</button>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($addresses as $addr)
            <div class="card" style="padding:20px;position:relative">
                @if($addr->is_default)
                    <span class="badge badge-ac" style="position:absolute;top:16px;left:16px">افتراضي</span>
                @endif
                <div style="font-weight:700;font-size:16px;color:var(--tx);margin-bottom:8px">{{ $addr->label }}</div>
                <div style="font-size:14px;color:var(--td);line-height:1.7;margin-bottom:16px">
                    {{ $addr->street ?? '' }}<br>
                    {{ $addr->city ?? '' }}{{ $addr->postal_code ? ' ' . $addr->postal_code : '' }}<br>
                    {{ $addr->country ?? '' }}
                    @if($addr->phone)
                        <br>{{ $addr->phone }}
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @if(!$addr->is_default)
                        <form action="{{ route('b2c.addresses.default', $addr->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-s">تعيين افتراضي</button>
                        </form>
                    @endif
                    <form action="{{ route('b2c.addresses.destroy', $addr->id) }}" method="POST"
                          onsubmit="return confirm('حذف هذا العنوان؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-dg">حذف</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Modal: عنوان جديد --}}
<div id="modal-address" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:20px;padding:32px;width:100%;max-width:500px;position:relative;max-height:90vh;overflow-y:auto">
        <button onclick="document.getElementById('modal-address').style.display='none'"
                style="position:absolute;top:16px;left:16px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--td)">✕</button>
        <h2 style="font-size:20px;font-weight:800;color:var(--tx);margin:0 0 20px">إضافة عنوان جديد</h2>
        <form action="{{ route('b2c.addresses.store') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                <div style="grid-column:span 2">
                    <label class="form-label">التسمية <span style="color:var(--dg)">*</span></label>
                    <input type="text" name="label" class="form-input" required placeholder="مثال: المنزل، المكتب">
                    @error('label')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div style="grid-column:span 2">
                    <label class="form-label">العنوان <span style="color:var(--dg)">*</span></label>
                    <input type="text" name="street" class="form-input" required placeholder="الشارع والحي">
                    @error('street')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">المدينة <span style="color:var(--dg)">*</span></label>
                    <input type="text" name="city" class="form-input" required placeholder="الرياض">
                    @error('city')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">الرمز البريدي</label>
                    <input type="text" name="postal_code" class="form-input" placeholder="12345">
                </div>
                <div>
                    <label class="form-label">الدولة <span style="color:var(--dg)">*</span></label>
                    <input type="text" name="country" class="form-input" required placeholder="SA" maxlength="2" style="text-transform:uppercase">
                    @error('country')<div style="color:var(--dg);font-size:12px;margin-top:4px">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label">رقم الهاتف</label>
                    <input type="text" name="phone" class="form-input" placeholder="+966">
                </div>
                <div style="grid-column:span 2;display:flex;align-items:center;gap:10px">
                    <input type="checkbox" name="is_default" value="1" id="chk-default" style="width:18px;height:18px">
                    <label for="chk-default" style="font-size:14px;color:var(--tx);cursor:pointer">تعيين كعنوان افتراضي</label>
                </div>
            </div>
            <button type="submit" class="btn btn-pr" style="width:100%">حفظ العنوان</button>
        </form>
    </div>
</div>
@endsection
