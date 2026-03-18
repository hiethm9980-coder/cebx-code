# مجلد أدوات تدقيق QA (Zero-Regression)

لا يتم تعديل أي كود تطبيقي من هنا — فقط تقارير واختبارات وفحوصات.

## الملفات

### تقارير الإصدار والتدقيق
- **PATCH_RELEASE_2026-02-24.md** — تقرير دفعة Zero Regression (حالة الإطلاق، ما تم إصلاحه، ما لم يُعدّل، اختبارات القبول، المرحلة التالية).
- **ZERO_REGRESSION_CHANGELOG.md** — قائمة التعديلات التفصيلية + Code Snippets.
- **FIXES.md** — مرجع تنفيذي للإصلاحات (12/12 BUG، 3/3 SEC): Change Log + مقاطع كود لكل ملف (Dashboard، routes، User، Wallet، PageController).

### تقارير ومصفوفات التغطية
- **QA_FULL_REPORT.md** — تقرير التدقيق (نسخة أولى): شاشات، أزرار، أمان، عزل مستأجر، توصيات.
- **coverage_matrix.csv** — مصفوفة تغطية مختصرة: screen, route, method, permission, ui_trigger, controller, db_effect, status.
- **DELIVERABLE-1-QA-REPORT.md** — تقرير فحص شامل مفصل (من ملف التسليم): 12 خطأ حرج، 7 أخطاء متوسطة، ثغرات أمان، مرجع أسماء الأعمدة، وتوصيات إصلاح.
- **DELIVERABLE-2-COVERAGE-MATRIX.csv** — مصفوفة تغطية كاملة لكل زر/عملية مع الحالة (OK/FAIL/WARN/DEAD) ومرجع الـ bug.
- **DELIVERABLE-3-TEST-SUITE-PLAN.md** — خطة مجموعة الاختبارات: Feature + Dusk، حالات اختبار جاهزة (Tenant Isolation، Wallet Ledger، Column Mismatch، إلخ)، وأوامر التشغيل.

### أدوات التحقق
- **RouteVerificationScript.php** — سكربت يجمّع أسماء المسارات من الـ views ويتحقق من تسجيلها (يتطلب تشغيله يدوياً مع bootstrap التطبيق).

## تشغيل التحقق من المسارات

### الطريقة 1: اختبار Feature (موصى به)

```bash
php artisan test tests/Feature/QaRouteCoverageTest.php
```

يفشل الاختبار إذا وُجد في الـ views استخدام لـ `route('...')` باسم مسار غير مسجّل (مثل `stores.disconnect`).

### الطريقة 2: السكربت (اختياري)

من جذر المشروع مع تحميل Laravel:

```bash
php artisan tinker
# ثم في Tinker:
require base_path('tests/qa/RouteVerificationScript.php');
# أو تشغيل المنطق يدوياً بعد تحميل التطبيق
```

## نتائج متوقعة

- **RouteVerification / QaRouteCoverageTest:** من المتوقع أن يظهر أن `stores.disconnect` مستخدم في الواجهة وغير معرّف في المسارات (فشل حتى يتم إصلاح الـ view أو إضافة المسار).
- باقي أسماء المسارات المستخدمة في الـ views مسجّلة في web.php أو web_b2b/web_b2c.

## Dusk (E2E)

لم يتم إضافة Laravel Dusk في هذا التدقيق. لتنفيذ E2E كامل (تسجيل دخول، تنقل، أزرار، تحقق DB):

1. تثبيت Dusk: `composer require --dev laravel/dusk`
2. `php artisan dusk:install`
3. إضافة اختبارات في `tests/Browser/` تغطي تسجيل الدخول لكل بوابة، التنقل، والنقر على الأزرار مع التحقق من عدم 404/405/500.

لا يتم تشغيل Dusk تلقائياً ضمن هذا التسليم.
