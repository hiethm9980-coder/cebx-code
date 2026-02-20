<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — Shipping Gateway</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">SG</div>
        <h1 style="color:var(--tx);font-size:22px;margin:0 0 6px">مرحباً بك</h1>
        <p style="color:var(--td);font-size:14px;margin:0 0 28px">سجل دخولك لبوابة إدارة الشحن</p>

        @if ($errors->any())
            <div style="padding:12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;margin-bottom:20px;text-align:right">
                @foreach ($errors->all() as $error)
                    <div style="color:var(--dg);font-size:13px">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="text-align:right">
            @csrf
            <div style="margin-bottom:16px">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@company.sa" class="form-input" required autofocus>
            </div>
            <div style="margin-bottom:16px">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" placeholder="••••••••" class="form-input" required>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
                <label style="display:flex;align-items:center;gap:8px;color:var(--tm);font-size:13px;cursor:pointer">
                    <input type="checkbox" name="remember"> تذكرني
                </label>
                <a href="#" style="color:var(--pr);font-size:13px;text-decoration:none">نسيت كلمة المرور؟</a>
            </div>
            <button type="submit" class="btn btn-pr" style="width:100%;padding:14px;font-size:16px;border-radius:12px">تسجيل الدخول</button>
        </form>
    </div>
</div>
</body>
</html>
