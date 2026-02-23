<form method="POST" action="{{ route('containers.store') }}">
    @csrf
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">رقم الحاوية *</label>
            <input type="text" name="container_number" class="form-control" value="{{ old('container_number') }}" required placeholder="MSKU1234567">
        </div>
        <div class="form-group">
            <label class="form-label">النوع</label>
            <select name="type" class="form-control">
                <option value="20ft">20ft Standard</option>
                <option value="40ft">40ft Standard</option>
                <option value="40hc">40ft High Cube</option>
                <option value="reefer">Reefer (مبرّد)</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">رمز ISO</label>
            <input type="text" name="iso_code" class="form-control" value="{{ old('iso_code') }}" placeholder="22G1">
        </div>
        <div class="form-group">
            <label class="form-label">الميناء</label>
            <input type="text" name="port" class="form-control" value="{{ old('port') }}" placeholder="جدة الإسلامي">
        </div>
    </div>
    <button type="submit" class="btn btn-pr" style="margin-top:12px">إنشاء حاوية</button>
</form>
