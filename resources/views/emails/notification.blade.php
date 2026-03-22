<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; direction: rtl; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a56db; padding: 24px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #374151; font-size: 15px; line-height: 1.7; }
        .footer { background: #f9fafb; padding: 16px 32px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name', 'بوابة الشحن') }}</h1>
        </div>
        <div class="body">
            @if ($notification->body_html)
                {!! $notification->body_html !!}
            @else
                {!! nl2br(e($notification->body)) !!}
            @endif
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }} &mdash; جميع الحقوق محفوظة
        </div>
    </div>
</body>
</html>
