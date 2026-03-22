# تقرير السيدرات والشاشات — cebx-code

## 1. السيدرات (Seeders)

### ✅ السيدرات التي تُشغّل بنجاح مع `php artisan db:seed`

| السيدر | الوصف |
|--------|--------|
| **DemoSeeder** | حسابات techco، mohammed-individual، مستخدم admin@system.sa، محافظ أساسية. (يتوقف مبكراً قبل الشحنات/المعاملات) |
| **RolesAndPermissionsSeeder** | الصلاحيات والأدوار الداخلية والمستأجرة |
| **NotificationTemplateSeeder** | قوالب الإشعارات (بريد، أحداث الشحن، إلخ) |
| **WaiverVersionSeeder** | إصدارات الإقرارات |
| **PricingParametersSeeder** | معاملات التسعير |

هذه الخمسة هي الوحيدة المُستدعاة حاليًا من `DatabaseSeeder`.

---

### ❌ السيدرات التي لا تُشغّل تلقائياً أو تفشل (هيكل الجداول مختلف)

| السيدر | السبب |
|--------|--------|
| **DemoAccountSeeder** | جدول `addresses`: لا يوجد عمود `contact_name` (الهيكل يستخدم `name` + `street`). جدول `wallets`: لا يوجد عمود `currency` أو `locked_balance` أو `status` بالشكل المتوقع. |
| **DevLoginSeeder** | يضيف مستخدمين لـ demo-company؛ إذا شُغّل بعد DemoAccountSeeder يسبب تكرار بريد (مثلاً admin@company.sa) لأن البريد فريد على مستوى الجدول. |
| **DevB2CSeeder** | يعمل على حساب demo-individual؛ لا يُستدعى من DatabaseSeeder. يمكن تشغيله يدوياً بعد التأكد من هيكل `users`. |
| **DevPlatformAdminSeeder** | نفس الفكرة؛ مستخدمون إداريون. لا يُستدعى من DatabaseSeeder. |
| **DemoDataSeeder** | يعتمد على وجود حساب `demo-company` (من DemoAccountSeeder) ومتاجر، شحنات، طلبات، إشعارات، تذاكر، تدقيق، KYC. يفشل أو يبقى غير مستدعى لأن DemoAccountSeeder لا يكتمل. |
| **HsCodeSeeder** | جدول `hs_codes`: عمود `description` غير موجود في الهيكل الحالي. |
| **FeatureFlagSeeder** | قد يفشل إذا جدول `feature_flags` أو أعمدةه تختلف. |
| **SystemSettingsSeeder** | قد يفشل إذا جدول `system_settings` أو أعمدةه تختلف. |
| **DhlStatusMappingSeeder** | يعتمد على جدول `status_mappings` وأعمدة محددة. |
| **CarrierSeeder** | يعتمد على جدول `status_mappings` (Aramex). |
| **E2EUserMatrixSeeder** | يُشغّل فقط عند `SEED_E2E_MATRIX=true` في `.env`؛ مخصص لاختبارات E2E. |

---

### تشغيل سيدر معيّن يدوياً

```bash
php artisan db:seed --class=DevB2CSeeder
php artisan db:seed --class=DevPlatformAdminSeeder
# بعد توحيد هيكل الجداول:
# php artisan db:seed --class=DemoAccountSeeder
# php artisan db:seed --class=DemoDataSeeder
```

---

## 2. الشاشات (الويب) — العاملة وغير العاملة

### بوابات الدخول (تعمل)

| الشاشة | المسار | ملاحظة |
|--------|--------|--------|
| اختيار البوابة | `/login` | تعمل |
| دخول B2C | `/b2c/login` | تعمل — بيانات: mohammed@example.sa / password |
| دخول B2B | `/b2b/login` | تعمل — بيانات: techco + sultan@techco.sa / password |
| دخول Admin | `/admin/login` | تعمل — بيانات: admin@system.sa / admin |

---

### B2B — بوابة الأعمال (بعد الدخول بحساب منظمة)

| الشاشة | المسار التقريبي | الحالة |
|--------|-----------------|--------|
| لوحة التحكم | `/b2b/dashboard` | ✅ تعمل |
| الشحنات (قائمة) | `/b2b/shipments` | ✅ تعمل (قائمة قد تكون فارغة) |
| إنشاء شحنة (مسودة) | `/b2b/shipments/create` | ⚠️ تعمل؛ قد تحتاج ناقلين/خدمات من API أو إعدادات |
| عرض شحنة | `/b2b/shipments/{id}` | ⚠️ تعمل إن وُجدت شحنات؛ وإلا لا توجد بيانات |
| عروض أسعار / إعلان / إصدار | `/b2b/shipments/{id}/offers` … | ⚠️ تعمل مع شحنة موجودة |
| الطلبات | `/b2b/orders` | ⚠️ تعمل؛ قائمة فارغة ما لم يُشغّل DemoDataSeeder |
| المتاجر | `/b2b/stores` | ⚠️ تعمل؛ فارغة بدون DemoDataSeeder |
| المستخدمون | `/b2b/users` | ✅ تعمل (مستخدمون techco من DemoSeeder) |
| الأدوار | `/b2b/roles` | ✅ تعمل |
| المحفظة | `/b2b/wallet` | ✅ تعمل (رصيد من DemoSeeder) |
| التقارير | `/b2b/reports` | ⚠️ تعمل؛ محتوى قد يكون محدوداً بدون بيانات |
| واجهة المطور / التكاملات / API Keys / Webhooks | `/b2b/developer/*` | ✅ تعمل (للأدوار ذات الصلاحية) |
| الإعدادات | `/b2b/settings` | ⚠️ تعتمد على وجود route وواجهة |

---

### B2C — بوابة الأفراد

| الشاشة | المسار التقريبي | الحالة |
|--------|-----------------|--------|
| لوحة التحكم | `/b2c/dashboard` | ✅ تعمل |
| الشحنات | `/b2c/shipments` | ✅ تعمل (غالباً فارغة) |
| إنشاء شحنة | `/b2c/shipments/create` | ⚠️ تعمل |
| التتبع | `/b2c/tracking` | ✅ تعمل |
| المحفظة | `/b2c/wallet` | ✅ تعمل |
| العناوين | `/b2c/addresses` | ⚠️ تعمل؛ قد تكون فارغة أو تعتمد على جدول addresses |
| الدعم | `/b2c/support` | ⚠️ تعمل؛ تذاكر فارغة بدون DemoDataSeeder |
| الإعدادات | `/b2c/settings` | ⚠️ تعتمد على route وواجهة |

---

### المسارات الداخلية (Internal / Admin)

| الشاشة | المسار التقريبي | الحالة |
|--------|-----------------|--------|
| الداخلية الرئيسية | `/internal` | ✅ تعمل (بعد دخول admin@system.sa) |
| اختيار الحساب (Tenant) | `/internal/tenant-context` | ✅ تعمل |
| لوحة الإدارة | `/admin` (إن وُجدت) | ⚠️ تعتمد على الصلاحيات والـ routes |

---

### شاشات تعتبر «غير شغالة» أو ناقصة من ناحية البيانات

1. **قوائم فارغة بدون بيانات تجريبية كافية**
   - B2B: الطلبات، المتاجر (تحتاج DemoDataSeeder + DemoAccountSeeder).
   - B2C: الشحنات، التتبع (بدون شحنات)، الدعم (بدون تذاكر).
   - أي شاشة تعتمد على بيانات من **DemoAccountSeeder** أو **DemoDataSeeder** ستظهر فارغة ما دامت هذه السيدرات لا تُشغّل بنجاح.

2. **سيدرات لا تُشغّل بسبب اختلاف هيكل الجداول**
   - **DemoAccountSeeder**: عناوين (أعمدة addresses)، محافظ (أعمدة wallets).
   - **DemoDataSeeder**: يعتمد على demo-company ومتاجر وشحنات وطلبات.
   - **HsCodeSeeder**: عمود `description` في `hs_codes`.

3. **شاشات قد تعرض أخطاء أو صفحات فارغة**
   - تفاصيل شحنة `/b2b/shipments/{id}` أو `/b2c/shipments/{id}` عند عدم وجود الشحنة أو عدم تطابق الهيكل.
   - أي صفحة تعتمد على **BillingWallet** أو **Wallet** بهيكل مختلف (مثلاً بدون currency/status).

---

## 3. التوصيات

1. **توحيد هيكل قاعدة البيانات مع السيدرات**
   - مراجعة migrations لجداول `addresses`, `wallets`, `hs_codes`, `feature_flags`, `system_settings`, `status_mappings` ومقارنتها بما يفعله كل سيدر.
   - إما تعديل السيدرات ليتوافقوا مع الهيكل الحالي، أو إضافة migrations لأعمدة ناقصة ثم إعادة تشغيل السيدرات.

2. **تشغيل السيدرات الإضافية يدوياً عند الحاجة**
   - بعد تطابق الهيكل: `DevB2CSeeder`, `DevPlatformAdminSeeder`, ثم `DemoAccountSeeder` ثم `DemoDataSeeder` حسب الترتيب والاعتماديات.

3. **للمستخدم الحالي**
   - `php artisan db:seed` يشغّل فقط الخمسة سيدرات الأساسية؛ الدخول B2B/B2C/Admin يعمل.
   - الشاشات التي تعتمد على بيانات تجريبية (طلبات، متاجر، شحنات كثيرة، تذاكر دعم) تبقى فارغة حتى يتم إصلاح وتشغيل DemoAccountSeeder و DemoDataSeeder.
