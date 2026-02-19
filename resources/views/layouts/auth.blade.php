<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'تسجيل الدخول') — Shipping Gateway</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @yield('content')
</body>
</html>
