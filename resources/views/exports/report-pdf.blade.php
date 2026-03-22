<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
    body  { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111; direction: rtl; }
    h1    { font-size: 15px; color: #1e3a5f; margin: 0 0 4px; }
    .meta { font-size: 10px; color: #6b7280; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th    { background: #1e3a5f; color: #fff; padding: 7px 8px; text-align: right; font-size: 10px; }
    td    { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .footer { margin-top: 14px; font-size: 9px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
<h1>{{ $title }}</h1>
<p class="meta">تاريخ الإنشاء: {{ $generated_at }} — CBEX Shipping Gateway</p>

@if(!empty($headers))
<table>
    <thead>
        <tr>
            @foreach($headers as $header)
            <th>{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            @foreach(array_values($row) as $cell)
            <td>{{ $cell }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#6b7280;">لا توجد بيانات للعرض.</p>
@endif

<p class="footer">تم إنشاء هذا التقرير تلقائياً — CBEX &copy; {{ date('Y') }}</p>
</body>
</html>
