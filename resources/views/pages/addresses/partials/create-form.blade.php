<form method="POST" action="{{ route('addresses.store') }}">
    @csrf
    <div class="form-group">
        <label class="form-label">التسمية (مثال: المستودع الرئيسي) *</label>
        <input type="text" name="label" class="form-control" value="{{ old('label') }}" required maxlength="100">
        @error('label') <span class="text-danger" style="font-size:11px">{{ $message }}</span> @enderror
    </div>
    <div class="form-group">
        <label class="form-label">العنوان الكامل *</label>
        <textarea name="full_address" class="form-control" rows="2" required maxlength="500" placeholder="الشارع، المدينة، الرمز البريدي">{{ old('full_address') }}</textarea>
        @error('full_address') <span class="text-danger" style="font-size:11px">{{ $message }}</span> @enderror
    </div>
    <button type="submit" class="btn btn-pr" style="margin-top:12px">إضافة العنوان</button>
</form>
