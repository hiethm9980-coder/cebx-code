<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'تسجيل الدخول') — Shipping Gateway</title>
    @include('components.pwa-meta')
    <meta name="pwa-sw-url" content="{{ asset('sw.js') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Tajawal', sans-serif; direction: rtl; }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        /* ── Branding Panel ── */
        .login-brand {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .login-brand::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            opacity: 0.08;
            top: -150px; right: -100px;
            pointer-events: none;
        }
        .login-brand::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            opacity: 0.05;
            bottom: -100px; left: -80px;
            pointer-events: none;
        }

        .brand-logo {
            width: 80px; height: 80px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .brand-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        .brand-title {
            font-size: 32px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .brand-desc {
            font-size: 16px;
            color: rgba(255,255,255,0.7);
            line-height: 1.8;
            max-width: 360px;
            position: relative;
            z-index: 1;
            margin-bottom: 40px;
        }
        .brand-features {
            list-style: none;
            padding: 0;
            text-align: right;
            position: relative;
            z-index: 1;
        }
        .brand-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            color: rgba(255,255,255,0.85);
            font-size: 15px;
            font-weight: 500;
        }
        .brand-features li span {
            font-size: 20px;
        }

        /* ── Form Panel ── */
        .login-form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
            background: #fff;
        }
        .login-form-box {
            width: 100%;
            max-width: 420px;
        }
        .form-title {
            font-size: 26px;
            font-weight: 800;
            color: #1E293B;
            margin-bottom: 6px;
        }
        .form-subtitle {
            font-size: 15px;
            color: #64748B;
            margin-bottom: 32px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-family: 'Tajawal', sans-serif;
            font-size: 15px;
            color: #1E293B;
            background: #F8FAFC;
            transition: all 0.2s ease;
            direction: ltr;
            text-align: right;
        }
        .form-group input:focus {
            outline: none;
            background: #fff;
        }
        .form-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .form-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #64748B;
            cursor: pointer;
        }
        .form-row a {
            font-size: 13px;
            text-decoration: none;
            font-weight: 600;
        }
        .login-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-family: 'Tajawal', sans-serif;
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 20px;
        }
        .login-btn:hover { transform: translateY(-2px); }
        .login-btn:active { transform: translateY(0); }

        .error-box {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .error-box p {
            font-size: 13px;
            margin: 4px 0;
        }
        .back-link {
            text-align: center;
            margin-top: 24px;
        }
        .back-link a {
            color: #64748B;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }
        .back-link a:hover { color: #334155; }

        .demo-credentials {
            margin-top: 28px;
            padding: 16px;
            border-radius: 12px;
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
        }
        .demo-credentials .demo-title {
            font-size: 12px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .demo-credentials .demo-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #475569;
            padding: 4px 0;
        }
        .demo-credentials code {
            background: #E2E8F0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            direction: ltr;
        }

        @media (max-width: 900px) {
            .login-page { grid-template-columns: 1fr; }
            .login-brand { display: none; }
            .login-form-panel { padding: 40px 24px; }
        }

        /* ── Portal-specific colors ── */
        @yield('portal-styles')
    </style>
</head>
<body>
<div class="login-page">
    {{-- Branding Panel --}}
    <div class="login-brand" style="@yield('brand-bg')">
        @yield('brand-content')
    </div>

    {{-- Form Panel --}}
    <div class="login-form-panel">
        <div class="login-form-box">
            <h1 class="form-title">@yield('form-title', 'تسجيل الدخول')</h1>
            <p class="form-subtitle">@yield('form-subtitle')</p>

            @if ($errors->any())
                <div class="error-box" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2)">
                    @foreach ($errors->all() as $error)
                        <p style="color:#DC2626">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="@yield('form-action')">
                @csrf
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="@yield('email-placeholder', 'you@example.sa')" required autofocus style="@yield('input-focus-style')">
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" placeholder="••••••••" required style="@yield('input-focus-style')">
                </div>
                <div class="form-row">
                    <label><input type="checkbox" name="remember"> تذكرني</label>
                    <a href="#" style="@yield('link-color')">نسيت كلمة المرور؟</a>
                </div>
                <button type="submit" class="login-btn" style="@yield('btn-style')">@yield('btn-text', 'تسجيل الدخول')</button>
            </form>

            @yield('demo-credentials')

            <div class="back-link">
                <a href="{{ route('login') }}">→ العودة لاختيار البوابة</a>
            </div>
        </div>
    </div>
</div>
<script>window.PWA={swUrl:'{{ asset("sw.js") }}',scope:'{{ rtrim(url("/"), "/") }}/'};</script>
<script src="{{ asset('js/pwa.js') }}" defer></script>
</body>
</html>
