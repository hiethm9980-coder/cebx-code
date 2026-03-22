<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Tahoma,sans-serif;direction:rtl;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 0;">
    <tr>
        <td align="center">
            <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

                <!-- Header -->
                <tr>
                    <td style="background:#1e3a5f;padding:28px 32px;">
                        <p style="margin:0;color:#fff;font-size:20px;font-weight:700;">CBEX Shipping Gateway</p>
                        <p style="margin:6px 0 0;color:#93c5fd;font-size:13px;">بوابة الشحن الذكي</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;font-size:16px;color:#111827;font-weight:600;">
                            تقريرك جاهز للتحميل
                        </p>
                        <p style="margin:0 0 24px;font-size:14px;color:#6b7280;line-height:1.7;">
                            التقرير <strong style="color:#1e3a5f;">{{ $reportName }}</strong>
                            جاهز الآن. يمكنك تحميله مباشرة عبر الزر أدناه.
                        </p>

                        <!-- Download Button -->
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background:#1e3a5f;border-radius:8px;padding:12px 28px;">
                                    <a href="{{ $downloadUrl }}"
                                       style="color:#fff;text-decoration:none;font-size:14px;font-weight:600;">
                                        تحميل التقرير
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:24px 0 0;font-size:12px;color:#9ca3af;">
                            إذا لم يعمل الزر، انسخ هذا الرابط في متصفحك:<br>
                            <span style="color:#1e3a5f;word-break:break-all;">{{ $downloadUrl }}</span>
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;">
                        <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;">
                            هذا البريد أُرسل تلقائياً من منصة CBEX — يُرجى عدم الرد عليه.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
