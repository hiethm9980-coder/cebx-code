# تقرير تدقيق QA — منصة Shipping Gateway (Zero-Regression)

**تاريخ التدقيق:** 2026-02-24  
**النطاق:** كل الشاشات، الأزرار، العمليات، الأمان، عزل المستأجر، قاعدة البيانات  
**القيود:** لم يتم تعديل أو حذف أي كود تطبيقي — تقرير واختبارات فقط.

---

## 1. الملخص التنفيذي

| المؤشر | العدد |
|--------|--------|
| **إجمالي المسارات المحمية (web.php)** | 80+ |
| **أزرار/نماذج تم فحصها** | 60+ |
| **فشل حرج (Dead Buttons / Wrong Route)** | 4 |
| **فشل أمني (عزل مستأجر)** | 1 |
| **عدم تطابق طريقة HTTP** | 4 |
| **فجوات صلاحيات (RBAC)** | 1 (لا middleware صلاحيات على Web) |

**النتيجة:** النظام **ليس** "Verified Production-Ready UI/Logic" وفق معايير التدقيق بسبب: أزرار ميتة، مسار خاطئ، وعزل مستأجر ناقص في قائمة الشحنات.

---

## 2. قائمة الأزرار/النماذج الفاشلة

| الشاشة | الزر/النموذج | Route المستخدم | المشكلة | التوصية (بدون تطبيق) |
|--------|-------------|----------------|---------|------------------------|
| **stores/index** | زر "فصل" (Disconnect) | `stores.disconnect` | المسار غير معرّف؛ المعرّف في web.php هو `stores.destroy` | تغيير الـ view لاستخدام `route('stores.destroy', $store)` مع `@method('DELETE')` |
| **containers/index** | نموذج "إضافة حاوية" (Modal) | `route('containers.index')` | النموذج يرسل POST إلى مسار GET فقط → 405 | تغيير action إلى `route('containers.store')` |
| **companies/index** | نموذج البحث/الإضافة | `route('companies.index')` | نموذج POST يرسل إلى مسار GET → 405 | فصل: GET للبحث، POST لـ store إن وُجد؛ أو إضافة مسار stores إن لم يكن |
| **risk/index** | نموذج داخل الصفحة | `route('risk.index')` | POST إلى مسار GET فقط → 405 | إما تحويل النموذج إلى GET أو إضافة route POST للعملية المطلوبة |
| **dg/index** | نموذج داخل الصفحة | `route('dg.index')` | POST إلى مسار GET فقط → 405 | نفس ما سبق |

---

## 3. فجوات الأمان والصلاحيات

### 3.1 عزل المستأجر (Tenant Isolation)

| المورد | المشكلة | الخطورة |
|--------|---------|----------|
| **ShipmentWebController::index()** | لا يوجد `where('account_id', auth()->user()->account_id)` — قائمة الشحنات تعرض شحنات **جميع** الحسابات | **حرجة** |

**توصية:** إضافة نطاق `account_id` في استعلام `index()` (وتعديل `$totalCount` بنفس النطاق). باقي الـ Controllers (Users, Wallet, Dashboard, Store, PageController, …) يطبقون `account_id` بشكل صحيح.

### 3.2 RBAC (صلاحيات على المسارات)

- **الوضع الحالي:** جميع المسارات المحمية في `web.php` تستخدم فقط `auth:web` و `tenant`. لا يوجد `permission:...` أو `CheckPermission` على أي route في الويب.
- **النتيجة:** أي مستخدم مسجّل ضمن نفس الحساب يمكنه الوصول لجميع الشاشات (المستخدمين، المحفظة، التقارير، الإعدادات، إلخ) إن وُجدت الروابط في القائمة.
- **توصية:** إضافة middleware صلاحيات على المسارات الحساسة (مثل المستخدمين، الأدوار، المحفظة، التقارير، الإعدادات) حسب سياسة المنتج، أو الاعتماد على التحقق داخل الـ Controller عبر `hasPermission()` حيث مطلوب.

### 3.3 CSRF و Session

- **CSRF:** النماذج التي تم فحصها تحتوي على `@csrf` — جيد.
- **تغيير حالة عبر GET:** لم يُلاحظ state-changing عبر GET في العينات المفحوصة؛ التعديلات تتم عبر POST/PATCH/DELETE.
- **Session/Token بعد تعطيل مستخدم:** تم توثيق أن `UserWebController::toggle()` يلغي الجلسات/التوكنات؛ لم يُتحقق منه عبر E2E في هذا التدقيق.

### 3.4 Rate Limiting

- **Web:** لم يُعثر على `throttle` على مسارات الويب (مثل تسجيل الدخول).
- **API:** مسارات الـ API تستخدم `throttle:10,1` وغيرها — موجود.
- **توصية:** إضافة rate limit على POST `/login` ومسارات بوابات الدخول لتقليل هجمات brute-force.

---

## 4. خريطة التغطية السريعة (الشاشات الرئيسية)

| الشاشة (View) | Route | الأزرار/الإجراءات | الحالة |
|--------------|--------|-------------------|--------|
| dashboard/index | dashboard | شحنة جديدة، عرض الكل، روابط البطاقات | OK (بدون نطاق account في Shipment::index) |
| shipments/index | shipments.* | تصدير، إنشاء، عرض، فلتر GET | OK |
| shipments/show | shipments.show | طباعة بوليصة، إلغاء، إرجاع، تتبع، دعم | OK |
| shipments/create | shipments.create, store | نموذج إنشاء شحنة | OK |
| users/index | users.* | دعوة مستخدم، تعديل | OK |
| users/edit | users.update | حفظ، تحديث كلمة المرور، تفعيل/تعطيل | OK |
| stores/index | stores.* | ربط متجر، مزامنة، **فصل** | **FAIL** (stores.disconnect غير معرّف) |
| orders/index | orders.* | شحن طلب | OK |
| wallet/index | wallet.* | شحن الرصيد، hold | OK |
| support/index, show | support.* | إنشاء تذكرة، رد، حل | OK |
| notifications/index | notifications.* | قراءة الكل | OK |
| addresses/index | addresses.* | إضافة، افتراضي، حذف | OK |
| roles/index | roles.* | عرض فقط (لا نموذج إضافة في الصفحة المعاينة) | OK |
| invitations/index | invitations.store | إرسال دعوة | OK |
| settings/index | settings.update | حفظ إعدادات | OK (PUT) |
| audit/index | audit.* | تصدير CSV، فلتر | OK |
| containers/index | containers.* | **إضافة حاوية (Modal)** | **FAIL** (POST → containers.index) |
| companies/index | companies.index | نموذج POST | **FAIL** (POST → GET route) |
| risk/index | risk.index | نموذج POST | **FAIL** |
| dg/index | dg.index | نموذج POST | **FAIL** |

---

## 5. التحقق من قاعدة البيانات (ملخص)

- **المحفظة (Wallet):** إنشاء محفظة عند أول زيارة، وتسجيل دفتر قيود (WalletLedgerEntry) عند الشحن — متوافق مع النطاق `account_id`.
- **المستخدمون:** إنشاء/تحديث/تعطيل مع نطاق `account_id` وفحص الملكية في toggle/destroy.
- **الشحنات:** إنشاء وعرض تفاصيل مع `account_id`؛ **قائمة index بدون نطاق** — انظر §3.1.
- **المتاجر، الطلبات، العناوين، الإشعارات، الدعوات، الأدوار، التقارير، التدقيق:** الاستعلامات تستخدم `account_id` في الـ Controllers المفحوصة.

---

## 6. البوابات B2B / B2C

- مسارات `web_b2b.php` و `web_b2c.php` مسجّلة وتعيد عرض لوحة واحدة (dashboard) لجميع الروابط الفرعية.
- الروابط في قوائم B2B/B2C (مثل b2b.shipments.index، b2c.tracking.index) **موجودة** وتؤدي إلى نفس الـ view؛ التطبيق الفعلي للـ CRUD يتم عبر المسار الموحّد بعد الدخول من `/login` (نفس الـ app مع sidebar من `layouts.app`).

---

## 7. معايير النجاح vs النتيجة

| المعيار | المطلوب | الوضع الحالي |
|---------|---------|----------------|
| 0 Dead Buttons | نعم | **فشل** — زر "فصل" يستخدم route غير معرّف |
| 0 Method Mismatch (405) | نعم | **فشل** — 4 نماذج POST → GET |
| 0 Cross-tenant leak | نعم | **فشل** — قائمة الشحنات بدون account_id |
| 0 Unauthorized access success | نعم | يعتمد على سياسة RBAC (لا صلاحيات على Web) |
| أثر مالي صحيح (Ledger) | نعم | محقق في Wallet (topup + ledger) |
| عمليات حرجة من واجهة إلى DB | نعم | محققة ما عدا النقاط أعلاه |

---

## 8. التوصيات ذات الأولوية (تقارير فقط — بدون تعديل كود)

1. **حرج:** إضافة نطاق `account_id` في `ShipmentWebController::index()` (وعدّ الإجمالي بنفس النطاق).
2. **حرج:** إصلاح زر "فصل" في `stores/index` لاستخدام `stores.destroy` مع method DELETE.
3. **عالي:** تصحيح action نموذج "إضافة حاوية" إلى `containers.store`.
4. **عالي:** تصحيح نماذج companies/risk/dg إما بمسارات POST مناسبة أو تحويل الطلبات إلى GET حيث يلزم.
5. **متوسط:** إضافة rate limiting على صفحات تسجيل الدخول (Web).
6. **منخفض:** توثيق سياسة RBAC وربطها بمسارات الويب أو التحقق داخل الـ Controllers.

---

## 9. مخرجات مجموعة الاختبارات (Deliverable 3)

### 9.1 اختبار تغطية المسارات

- **الملف:** `tests/Feature/QaRouteCoverageTest.php`
- **الأمر:** `php artisan test tests/Feature/QaRouteCoverageTest.php`
- **النتيجة المتوقعة عند التشغيل:** **فشل** — لأن الواجهة تستخدم `route('stores.disconnect', $store)` والمسار المعرّف هو `stores.destroy` فقط. رسالة الفشل: `Route names used in views but not defined: stores.disconnect`.
- بعد إصلاح الـ view (استخدام `stores.destroy` بدلاً من `stores.disconnect`) يصبح الاختبار ناجحاً.

### 9.2 Dusk (E2E)

- لم يتم تثبيت أو تشغيل Laravel Dusk في هذا التدقيق.
- التوصية: تثبيت Dusk وإضافة اختبارات في `tests/Browser/` لتسجيل الدخول (كل بوابة)، التنقل في الـ Sidebar، والنقر على الأزرار مع التحقق من عدم 404/405/500، ثم التحقق من أثر DB حيث يلزم.

### 9.3 السجلات ولقطات الشاشة

- لا توجد لقطات شاشة أو سجلات إضافية مرفقة؛ التدقيق استند إلى تحليل ثابت للكود وملفات المسارات والـ views.
- لتسجيل فشل عند التشغيل الفعلي: تشغيل الاختبار أعلاه وحفظ المخرجات، أو تشغيل Dusk مع `screenshot()` عند الفشل.

---

*تم إعداد هذا التقرير آلياً في إطار تدقيق Zero-Regression. لم يتم تغيير أي كود تطبيقي.*
