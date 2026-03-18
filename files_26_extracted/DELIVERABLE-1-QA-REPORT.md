# 🔍 تقرير الفحص الشامل — Shipping Gateway QA Audit
### التاريخ: 2026-02-24 | المنهجية: Static Code Analysis + Cross-Validation (Zero-Regression)
### المصادر: Project Knowledge (source of truth) + Database Migrations + Model definitions

---

## 📊 A) ملخص تنفيذي

| المقياس | القيمة |
|---------|--------|
| إجمالي الشاشات | **40** (31 رئيسية + 9 فرعية) |
| إجمالي الأزرار/العمليات | **82** عملية فريدة |
| إجمالي Web Routes | **66** route |
| ✅ يعمل بشكل صحيح | **54** |
| ❌ خطأ حرج (500/405/crash) | **12** |
| ⚠️ خطأ متوسط (منطق/عرض خاطئ) | **7** |
| 🟡 زر بلا تأثير (Dead/Stub) | **3** |
| 🔒 ثغرة أمنية | **8** |
| 🔵 تحسين مُوصى به | **5** |

### 🔴 الحكم: **غير جاهز للإنتاج**
- 12 خطأ حرج يمنع استخدام 8 شاشات
- 2 ثغرتان أمنيتان عالية الخطورة (RBAC + Tenant Bypass)
- 3 عمليات مالية بلا ledger trail

---

## 🏗️ B) منهجية الفحص

### B.1 مصادر البيانات المستخدمة
| المصدر | الغرض |
|--------|-------|
| `routes/web.php` | خريطة كل الروابط (66 route) |
| `routes/api.php` | مقارنة مع API لتأكيد أسماء الأعمدة |
| 8 Web Controllers | كل منطق العمليات |
| 126 Model | `$fillable` + `$casts` + relationships = أسماء الأعمدة الصحيحة |
| 27 Migrations (128 tables) | Schema الحقيقي في DB |
| Blade Views | أسماء الحقول في Forms + عرض البيانات |
| `BelongsToAccount` trait | عزل البيانات بين المستأجرين |
| `TenantMiddleware` | فحص الحساب عند كل request |

### B.2 طريقة التحقق من كل عملية
```
View → Button/Form → Route (web.php) → Controller@method → validate() → Model::create/update
                                                                            ↓
                                                              Model $fillable ← Migration columns
```
إذا وُجد عدم تطابق في أي نقطة ← يُسجَّل كـ BUG.

---

## 🚨 C) القسم 1: الأخطاء الحرجة (12 خطأ)

---

### ❌ BUG-01: صفحة الحاويات — أعمدة غير موجودة
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/containers` |
| **الزر** | فتح الصفحة |
| **Route** | `GET /containers` → `PageController@containers` |
| **الكود الخاطئ** | `$c->iso_code`, `$c->port`, `$c->vessel?->name`, `$c->eta` |
| **Model الحقيقي** | Container: `$fillable` = `container_number, size, type, location, status, vessel_schedule_id` |
| **الأعمدة الصحيحة** | `size` (لا `iso_code`), `location` (لا `port`), `vesselSchedule?->vessel?->vessel_name` (لا `vessel?->name`), `vesselSchedule?->eta` (لا `eta`) |
| **Migration** | `$t->string('size')`, `$t->string('location')` — لا يوجد `iso_code` ولا `port` |
| **الأثر** | ⚡ كل بيانات الجدول تظهر فارغة (null) — لا يعطل الصفحة لكن البيانات غير صحيحة |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-02: إضافة حاوية — 405 Method Not Allowed
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/containers` |
| **الزر** | "+ إنشاء حاوية" |
| **Route المطلوب** | `POST /containers` |
| **web.php الحالي** | `Route::get('/containers', ...)` فقط — **لا يوجد POST** |
| **الأثر** | ⛔ 405 — النموذج لا يُرسَل |
| **الخطورة** | 🔴 حرج |
| **ملاحظة إضافية** | `containersStore()` method موجود في PageController لكن يستخدم `iso_code`/`port` (خاطئ) |

---

### ❌ BUG-03: صفحة الجمارك — Unknown column 'status'
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/customs` |
| **الزر** | فتح الصفحة |
| **Route** | `GET /customs` → `PageController@customs` |
| **الكود الخاطئ** | `$c->status`, `$c->type`, `$c->duties_amount` |
| **Model الحقيقي** | CustomsDeclaration: `customs_status`, `declaration_type`, `duty_amount` |
| **Migration** | `$t->enum('customs_status', [...])` — لا يوجد عمود `status` |
| **الأثر** | 💥 SQL Error 1054: Unknown column → الصفحة لا تفتح |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-04: إنشاء إقرار جمركي — أعمدة خاطئة
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/customs` |
| **الزر** | "إنشاء إقرار" |
| **Route** | `POST /customs` → `PageController@customsStore` |
| **الكود الخاطئ** | `'type'` validation key, `'status' => 'pending'`, `'currency' => 'SAR'` |
| **الأعمدة الصحيحة** | `declaration_type`, `customs_status => 'draft'`, `declared_currency => 'SAR'` |
| **الأثر** | 💥 خطأ SQL أو سجل يُنشأ بأعمدة خاطئة تُهمَل |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-05: صفحة السفن — أعمدة غير موجودة
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/vessels` |
| **الكود الخاطئ** | `$v->name`, `$v->capacity`, `$v->route` |
| **Model الحقيقي** | Vessel: `vessel_name`, `capacity_teu`, `operator` |
| **Migration** | `$t->string('vessel_name', 200)` — لا يوجد عمود `name` |
| **الأثر** | كل بيانات السفن تظهر فارغة |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-06: إضافة سفينة — 405 Method Not Allowed
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/vessels` |
| **الزر** | "+ إنشاء سفينة" |
| **Route المطلوب** | `POST /vessels` |
| **web.php** | فقط `GET /vessels` — **لا يوجد POST** |
| **الأثر** | ⛔ 405 |
| **الخطورة** | 🔴 حرج |
| **ملاحظة** | لا يوجد `vesselsStore()` method في PageController أصلاً |

---

### ❌ BUG-07: صفحة الجداول البحرية — أعمدة خاطئة
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/schedules` |
| **الكود الخاطئ** | `$s->vessel?->name`, `$s->route`, `$s->departure_date`, `$s->arrival_date` |
| **الأعمدة الصحيحة** | `vessel?->vessel_name`, `service_route`, `etd`, `eta` |
| **VesselSchedule $fillable** | `vessel_id, voyage_number, service_route, etd, eta, ...` |
| **الأثر** | كل البيانات فارغة |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-08: Dashboard — رصيد المحفظة خاطئ
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/` (لوحة التحكم) |
| **الكود** | `DashboardController@index`: `$walletBalance = $wallet?->balance ?? 0` |
| **المشكلة** | Wallet model لا يملك خاصية `balance`. الخاصية الصحيحة: `available_balance` |
| **الأثر** | يعرض 0 دائماً بدلاً من الرصيد الحقيقي |
| **الخطورة** | 🔴 حرج (معلومة مالية خاطئة) |

---

### ❌ BUG-09: التتبع — Unknown column 'carrier_tracking_number'
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/tracking` |
| **الزر** | بحث بالتتبع |
| **Route** | `GET /tracking` → `PageController@tracking` |
| **الكود الخاطئ** | `->orWhere('carrier_tracking_number', $ref)` |
| **Shipment $fillable** | `tracking_number`, `carrier_shipment_id` — لا يوجد `carrier_tracking_number` |
| **الأثر** | 💥 SQL Error 1054 عند البحث |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-10: السائقين — أعمدة خاطئة في العرض
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/drivers` |
| **الكود الخاطئ** | `$d->plate_number`, `$d->deliveries_count`, `$d->zone` |
| **Driver $fillable** | `vehicle_plate`, `total_deliveries`, `zones` (JSON) |
| **Migration** | `$t->string('vehicle_plate', 30)`, `$t->integer('total_deliveries')`, `$t->json('zones')` |
| **الأثر** | 3 أعمدة تظهر null/0 بدل القيم الحقيقية |
| **الخطورة** | ⚠️ متوسط (الصفحة تفتح لكن البيانات خاطئة) |

---

### ❌ BUG-11: المطالبات — أعمدة خاطئة في العرض والإنشاء
| البند | القيمة |
|-------|--------|
| **الشاشة** | `/claims` |
| **الكود الخاطئ (عرض)** | `$c->type`, `$c->amount`, `$c->customer_name` |
| **Claim $fillable** | `claim_type`, `claimed_amount` — لا يوجد `customer_name` إطلاقاً |
| **الكود الخاطئ (إنشاء)** | `claimsStore()` يمرر `type`, `amount`, `customer_name` |
| **الأثر** | عرض: بيانات خاطئة. إنشاء: سجل مكسور |
| **الخطورة** | 🔴 حرج |

---

### ❌ BUG-12: لا يوجد `createForm` للحاويات والجمارك (في الكود الأصلي)
| البند | القيمة |
|-------|--------|
| **الشاشات** | `/containers`, `/customs`, `/vessels` |
| **المشكلة** | `containers()` method في الكود الأصلي لا يمرر `createForm` → زر الإنشاء يظهر لكن المودال فارغ |
| **الخطورة** | 🔴 حرج (يمنع إنشاء سجلات حتى لو أُضيف الـ route) |

---

## ⚠️ D) القسم 2: أخطاء متوسطة (7 أخطاء)

| # | الشاشة | الوصف | التأثير |
|---|--------|-------|---------|
| WARN-01 | `/stores` | `sync()` method = stub — يعيد رسالة نجاح بدون مزامنة حقيقية | 🟡 Dead button |
| WARN-02 | `/stores` | `test()` method = stub — يعيد رسالة نجاح بدون اختبار فعلي | 🟡 Dead button |
| WARN-03 | `/orders` | لا يوجد route لـ "مزامنة الطلبات" (الزر يُنشئ POST بدون route) | 🟡 Dead button |
| WARN-04 | Sidebar | `Notification::where('read_at', null)->count()` — Notification model قد لا يستخدم `BelongsToAccount`، فيحسب إشعارات **كل الحسابات** | ⚠️ عرض خاطئ |
| WARN-05 | Sidebar | `SupportTicket::where('status', 'open')->count()` — نفس المشكلة | ⚠️ عرض خاطئ |
| WARN-06 | `/wallet/hold` | `WalletWebController@hold` لا يُسجّل في `wallet_ledger_entries` — يحرّك الرصيد بدون أثر في الدفتر | ⚠️ بيانات مالية بلا تدقيق |
| WARN-07 | `/shipments/create` | لا يوجد `GET /shipments/create` route — إذا كان هناك رابط مباشر للصفحة سيعطي 404 | ⚠️ خطأ تنقّل |

---

## 🔒 E) القسم 3: فحص الأمان الشامل

### E.1 المصادقة (Authentication)
| الفحص | النتيجة | الدليل |
|-------|---------|--------|
| تسجيل الدخول يتطلب email + password | ✅ | `AuthWebController@login`: `$request->validate(['email'=>'required|email', 'password'=>'required'])` |
| Session regeneration بعد login | ✅ | `$request->session()->regenerate()` |
| Session invalidation عند logout | ✅ | `$request->session()->invalidate()` + `regenerateToken()` |
| CSRF token regeneration عند logout | ✅ | `$request->session()->regenerateToken()` |
| Logout عبر POST فقط | ✅ | `Route::post('/logout')` |
| Remember me token | ✅ | `Auth::attempt($credentials, $request->boolean('remember'))` |
| Rate limiting على Web login | ❌ **غير موجود** | لا يوجد `throttle` middleware على `POST /login` |
| Rate limiting على API login | ✅ | `middleware('throttle:5,1')` |
| Password hashing | ✅ | User model: `'password' => 'hashed'` cast |

### E.2 التفويض (RBAC)
| الفحص | النتيجة | الدليل |
|-------|---------|--------|
| Permission middleware على Web routes | ❌ **غير موجود** | كل routes محمية بـ `auth:web` + `tenant` فقط |
| Permission middleware على API routes | ✅ | عبر Service layer |
| User model يملك `hasPermission()` | ✅ | يفحص عبر roles → permissions |
| Owner implicit permissions | ✅ | `if ($this->is_owner) return true` |
| Blade `@can` directives | ❌ **غير مستخدمة** | لا يوجد أي فحص صلاحيات في views |
| أي مستخدم يمكنه الوصول لأي شاشة | ❌ **ثغرة** | operator يمكنه حذف مستخدمين، شحن محفظة، تعديل إعدادات |

### E.3 عزل البيانات (Tenant Isolation)
| الفحص | النتيجة | الدليل |
|-------|---------|--------|
| TenantMiddleware يُحدد `current_account_id` | ✅ | `app()->instance('current_account_id', $user->account_id)` |
| `BelongsToAccount` trait يُرشّح تلقائياً | ✅ | `AccountScope: where(table.account_id = current_account_id)` |
| Auto-assign account_id عند الإنشاء | ✅ | `static::creating(fn($m) => ...)` |
| Shipment: tenant-isolated | ✅ | uses `BelongsToAccount` |
| Order: tenant-isolated | ✅ | uses `BelongsToAccount` |
| Store: tenant-isolated | ✅ | uses `BelongsToAccount` |
| Wallet: tenant-isolated | ✅ | uses `BelongsToAccount` |
| Container/Vessel/Claim/Driver: tenant-isolated | ✅ | uses `BelongsToAccount` |
| **User: NOT tenant-isolated (by design)** | ⚠️ | User comment: "intentionally does NOT use BelongsToAccount" — لكن `UserWebController@index` يرشّح يدوياً: `User::where('account_id', ...)` |
| **User toggle/destroy: NO account check** | ❌ **ثغرة** | `toggle(User $user)` / `destroy(User $user)` — Route Model Binding بلا global scope = يمكن استهداف مستخدم حساب آخر بتخمين UUID |

### E.4 CSRF
| الفحص | النتيجة |
|-------|---------|
| `<meta name="csrf-token">` في layout | ✅ |
| `@csrf` في كل Blade forms | ✅ (تم التحقق من كل الـ forms) |
| لا يوجد state-change عبر GET | ✅ (cancel/delete/update كلها POST/PATCH/DELETE) |
| VerifyCsrfToken middleware مفعّل | ✅ (Laravel default) |

### E.5 حماية الإدخال
| الفحص | النتيجة |
|-------|---------|
| Laravel `$request->validate()` على كل store/update | ✅ |
| Blade auto-escaping `{{ }}` | ✅ |
| Raw HTML output `{!! !!}` | ⚠️ `PageController` يبني HTML strings (`statusBadge`, `td-link`) — آمن لأن القيم from DB لا من user input مباشرة |
| Eloquent ORM (no raw SQL) | ✅ |
| Mass Assignment protection (`$fillable`) | ✅ على كل 126 model |
| SQL Injection vectors | ✅ محمي — لا يوجد `DB::raw()` مكشوف |

### E.6 Rate Limiting
| الفحص | النتيجة |
|-------|---------|
| API public routes | ✅ `throttle:10,1` |
| API login | ✅ `throttle:5,1` |
| API forgot-password | ✅ `throttle:3,1` |
| Web login | ❌ **غير موجود** — يمكن brute-force |
| Web forms (topup, create) | ❌ **غير موجود** |

### E.7 Session Security
| الفحص | النتيجة |
|-------|---------|
| Session driver | ✅ Laravel default (file/database) |
| Session lifetime | ✅ default 120 min |
| تعطيل مستخدم يُلغي جلسته | ❌ **لا** — `UserWebController@toggle` يغيّر `status` فقط ولا يُبطل الجلسة |
| حذف مستخدم يُلغي جلسته | ❌ **لا** — SoftDelete فقط، الجلسة تبقى نشطة |

---

### 🔒 ملخص الثغرات الأمنية

| # | الثغرة | الخطورة | التفصيل |
|---|--------|---------|---------|
| **SEC-01** | لا يوجد RBAC على Web Routes | 🔴 **عالية** | كل مستخدم مسجّل في حساب يمكنه الوصول لـ 40 شاشة و 82 عملية بلا فحص صلاحيات |
| **SEC-02** | User toggle/destroy بلا tenant check | 🔴 **عالية** | User model لا يستخدم BelongsToAccount → Route Model Binding يمكن أن يجد مستخدم من حساب آخر |
| **SEC-03** | لا rate limiting على Web login | ⚠️ **متوسطة** | يمكن brute-force |
| **SEC-04** | تعطيل مستخدم لا يُبطل جلسته | ⚠️ **متوسطة** | المستخدم المعطّل يبقى مسجّلاً |
| **SEC-05** | Store destroy بلا permission | ⚠️ **متوسطة** | أي مستخدم يحذف أي متجر في حسابه |
| **SEC-06** | Order ship/cancel بلا permission | ⚠️ **متوسطة** | أي مستخدم يشحن/يلغي أي طلب |
| **SEC-07** | Wallet topup بلا permission | ⚠️ **متوسطة** | أي مستخدم يشحن الرصيد |
| **SEC-08** | Hold بلا ledger entry | 🟡 **منخفضة-متوسطة** | يحرّك المال بدون audit trail |

---

## 📊 F) القسم 4: خريطة DB Effects الكاملة

### F.1 عمليات Create

| العملية | Route | Controller | الجدول | الأعمدة المُمرَّرة | التطابق مع $fillable | الحالة |
|---------|-------|------------|--------|-------------------|---------------------|--------|
| إنشاء شحنة | `POST /shipments` | ShipmentWebController@store | `shipments` | `account_id, created_by, reference_number, source, status, carrier_code, tracking_number, sender_*, recipient_*, total_weight, total_charge` | ✅ كلها في `$fillable` | ✅ OK |
| إنشاء طلب | `POST /orders` | OrderWebController@store | `orders` | `account_id, store_id, external_order_id, external_order_number, source, status, customer_name, customer_email, total_amount, currency, shipping_address_line_1` | ✅ | ✅ OK |
| ربط متجر | `POST /stores` | StoreWebController@store | `stores` | `name, platform, url, status, account_id` | ✅ | ✅ OK |
| شحن رصيد | `POST /wallet/topup` | WalletWebController@topup | `wallets` + `wallet_ledger_entries` | wallet: `increment(available_balance)`. ledger: `wallet_id, type='topup', amount, running_balance, description, created_at` | ✅ | ✅ OK |
| حجز رصيد | `POST /wallet/hold` | WalletWebController@hold | `wallets` فقط | `decrement(available_balance)`, `increment(locked_balance)` | ⚠️ بلا ledger | ⚠️ WARN-06 |
| إنشاء مستخدم | `POST /users` | UserWebController@store | `users` + `user_role` | `name, email, password, account_id` + pivot attach | ✅ | ✅ OK |
| إنشاء تذكرة | `POST /support` | SupportWebController@store | `support_tickets` | `ticket_number, subject, priority, status='open', account_id` | ✅ | ✅ OK |
| رد تذكرة | `POST /support/{id}/reply` | SupportWebController@reply | `support_ticket_replies` | `support_ticket_id, message, user_id, is_customer=false` | ✅ | ✅ OK |
| إنشاء دور | `POST /roles` | PageController@rolesStore | `roles` | `name, account_id` | ✅ | ✅ OK |
| إرسال دعوة | `POST /invitations` | PageController@invitationsStore | `invitations` | `email, role_id, token, status, invited_by, account_id` | ✅ | ✅ OK |
| إنشاء عنوان | `POST /addresses` | PageController@addressesStore | `addresses` | `label, address_line_1, city, country, account_id` | ✅ | ✅ OK |
| إنشاء تسعير | `POST /pricing` | PageController@pricingStore | `pricing_rule_sets` | `account_id, name, description, status, is_default, created_by` | ✅ | ✅ OK |
| إنشاء منظمة | `POST /organizations` | PageController@organizationsStore | `organizations` | `name, type, account_id` | ✅ | ✅ OK |
| إنشاء حاوية | `POST /containers` | PageController@containersStore | `containers` | يمرر `iso_code, port` | ❌ الأعمدة: `size, location` | ❌ BUG-02 |
| إنشاء إقرار | `POST /customs` | PageController@customsStore | `customs_declarations` | يمرر `type, status='pending', currency` | ❌ الأعمدة: `declaration_type, customs_status='draft', declared_currency` | ❌ BUG-04 |
| إنشاء مطالبة | `POST /claims` | PageController@claimsStore | `claims` | يمرر `type, amount, customer_name` | ❌ الأعمدة: `claim_type, claimed_amount` — لا يوجد `customer_name` | ❌ BUG-11 |
| إنشاء سفينة | `POST /vessels` | — | `vessels` | — | ❌ لا يوجد route ولا method | ❌ BUG-06 |

### F.2 عمليات Update

| العملية | Route | الجدول | التغيير | Tenant-Scoped | الحالة |
|---------|-------|--------|---------|---------------|--------|
| إلغاء شحنة | `PATCH /shipments/{id}/cancel` | `shipments` | `status → 'cancelled'` | ✅ BelongsToAccount | ✅ OK |
| شحن طلب | `POST /orders/{id}/ship` | `orders` | `status → 'shipped'` | ✅ | ✅ OK |
| إلغاء طلب | `PATCH /orders/{id}/cancel` | `orders` | `status → 'cancelled'` | ✅ | ✅ OK |
| تعطيل مستخدم | `PATCH /users/{id}/toggle` | `users` | `status ↔ active/suspended` | ❌ **بلا scope** | ❌ SEC-02 |
| حل تذكرة | `PATCH /support/{id}/resolve` | `support_tickets` | `status → 'resolved'` | ✅ | ✅ OK |
| قراءة إشعار | `PATCH /notifications/{id}/read` | `notifications` | `read_at → now()` | ✅ | ✅ OK |
| قراءة كل الإشعارات | `POST /notifications/read-all` | `notifications` | `read_at → now()` (mass) | ✅ | ✅ OK |
| تعيين عنوان افتراضي | `PATCH /addresses/{id}/default` | `addresses` | `is_default_sender → true` | ✅ | ✅ OK |
| تحديث إعدادات | `PUT /settings` | `account_settings` | upsert settings | ✅ | ✅ OK |

### F.3 عمليات Delete

| العملية | Route | الجدول | النوع | Tenant-Scoped | الحالة |
|---------|-------|--------|-------|---------------|--------|
| حذف مستخدم | `DELETE /users/{id}` | `users` | Soft Delete | ❌ **بلا scope** | ❌ SEC-02 |
| حذف متجر | `DELETE /stores/{id}` | `stores` | Hard Delete | ✅ BelongsToAccount | ✅ OK |
| حذف عنوان | `DELETE /addresses/{id}` | `addresses` | Delete | ✅ | ✅ OK |

---

## 📋 G) القسم 5: مصفوفة التغطية الكاملة (كل زر)

### G.1 لوحة التحكم `/`

| الزر/العنصر | Route | HTTP | Controller | DB | الحالة |
|------------|-------|------|------------|-----|--------|
| أيقونة لوحة التحكم | `GET /` | GET | DashboardController@index | قراءة `shipments`, `orders`, `wallets`, `notifications` | ⚠️ BUG-08: `balance` خاطئ |
| رابط "آخر الشحنات" | `GET /shipments/{id}` | GET | ShipmentWebController@show | قراءة shipment واحد | ✅ |

### G.2 الشحنات `/shipments`

| الزر/العنصر | Route | HTTP | Controller | DB | الحالة |
|------------|-------|------|------------|-----|--------|
| صفحة الشحنات | `GET /shipments` | GET | ShipmentWebController@index | قراءة + paginate + filter | ✅ |
| tab "الكل" | `GET /shipments` | GET | — | بلا filter | ✅ |
| tab "بانتظار الدفع" | `GET /shipments?status=payment_pending` | GET | — | filter by status | ✅ |
| tab "في الطريق" | `GET /shipments?status=in_transit` | GET | — | filter | ✅ |
| tab "مُسلّم" | `GET /shipments?status=delivered` | GET | — | filter | ✅ |
| tab "ملغي" | `GET /shipments?status=cancelled` | GET | — | filter | ✅ |
| بحث | `GET /shipments?search=X` | GET | — | LIKE on tracking_number, carrier_shipment_id, recipient_name | ✅ |
| "+ إنشاء شحنة" (modal) | `POST /shipments` | POST | ShipmentWebController@store | insert into `shipments` | ✅ |
| "📥 تصدير" | `GET /shipments/export` | GET | ShipmentWebController@export | read → CSV | ✅ |
| رابط شحنة → تفاصيل | `GET /shipments/{id}` | GET | ShipmentWebController@show | read + timeline | ✅ |
| "إلغاء الشحنة" | `PATCH /shipments/{id}/cancel` | PATCH | ShipmentWebController@cancel | `status → cancelled` | ✅ |
| "إنشاء مرتجع" | `POST /shipments/{id}/return` | POST | ShipmentWebController@createReturn | insert return shipment | ✅ |
| "طباعة البوليصة" | `GET /shipments/{id}/label` | GET | ShipmentWebController@label | read | ✅ |

### G.3 الطلبات `/orders`

| الزر | Route | HTTP | DB | الحالة |
|------|-------|------|----|--------|
| صفحة الطلبات | `GET /orders` | GET | read + paginate | ✅ |
| "+ إنشاء طلب" | `POST /orders` | POST | insert `orders` | ✅ |
| "شحن" | `POST /orders/{id}/ship` | POST | `status → shipped` | ✅ (⚠️ SEC-06) |
| "إلغاء" | `PATCH /orders/{id}/cancel` | PATCH | `status → cancelled` | ✅ |
| "مزامنة الطلبات" | — | — | — | 🟡 Dead button (WARN-03) |

### G.4 المتاجر `/stores`

| الزر | Route | HTTP | DB | الحالة |
|------|-------|------|----|--------|
| صفحة المتاجر | `GET /stores` | GET | read + count orders | ✅ |
| "ربط متجر" | `POST /stores` | POST | insert `stores` | ✅ |
| "مزامنة" | `POST /stores/{id}/sync` | POST | **لا شيء** (stub) | 🟡 WARN-01 |
| "اختبار" | `POST /stores/{id}/test` | POST | **لا شيء** (stub) | 🟡 WARN-02 |
| "حذف" | `DELETE /stores/{id}` | DELETE | hard delete | ✅ |

### G.5 التتبع `/tracking`

| الزر | Route | HTTP | DB | الحالة |
|------|-------|------|----|--------|
| صفحة التتبع | `GET /tracking` | GET | read active shipments | ❌ BUG-09 |
| "بحث" | `GET /tracking?q=X` | GET | LIKE on wrong column | ❌ BUG-09 |

### G.6 المحفظة `/wallet`

| الزر | Route | HTTP | DB | الحالة |
|------|-------|------|----|--------|
| صفحة المحفظة | `GET /wallet` | GET | read wallet + ledger + payment methods | ✅ |
| "شحن الرصيد" | `POST /wallet/topup` | POST | increment wallet + insert ledger entry | ✅ |
| "حجز مبلغ" | `POST /wallet/hold` | POST | decrement available + increment locked | ⚠️ WARN-06: no ledger |

### G.7 المستخدمين `/users`

| الزر | Route | HTTP | DB | الحالة |
|------|-------|------|----|--------|
| صفحة المستخدمين | `GET /users` | GET | read scoped by account_id | ✅ |
| "إضافة مستخدم" | `POST /users` | POST | insert users + attach role | ✅ |
| "تعطيل/تفعيل" | `PATCH /users/{id}/toggle` | PATCH | toggle status | ⚠️ SEC-02: no tenant check |
| "حذف" | `DELETE /users/{id}` | DELETE | soft delete | ⚠️ SEC-02: no tenant check |

### G.8 الدعم الفني `/support`

| الزر | Route | HTTP | DB | الحالة |
|------|-------|------|----|--------|
| صفحة الدعم | `GET /support` | GET | read tickets + paginate | ✅ |
| "إنشاء تذكرة" | `POST /support` | POST | insert `support_tickets` | ✅ |
| تفاصيل تذكرة | `GET /support/{id}` | GET | read ticket + replies | ✅ |
| "إرسال رد" | `POST /support/{id}/reply` | POST | insert `support_ticket_replies` | ✅ |
| "حل التذكرة" | `PATCH /support/{id}/resolve` | PATCH | `status → resolved` | ✅ |

### G.9 شاشات PageController (نظام)

| الشاشة | Route | أزرار | الحالة |
|--------|-------|-------|--------|
| التسعير `/pricing` | GET+POST | عرض + إنشاء قاعدة | ✅ |
| المالية `/financial` | GET | عرض فقط | ✅ |
| الأدوار `/roles` | GET+POST | عرض + إنشاء دور | ✅ |
| الدعوات `/invitations` | GET+POST | عرض + إرسال دعوة | ✅ |
| الإشعارات `/notifications` | GET+PATCH+POST | عرض + قراءة + قراءة الكل | ✅ |
| العناوين `/addresses` | GET+POST+PATCH+DELETE | كامل CRUD + default | ✅ |
| الإعدادات `/settings` | GET+PUT | عرض + حفظ | ✅ |
| سجل التدقيق `/audit` | GET | عرض + تصدير | ✅ |
| لوحة الإدارة `/admin` | GET | عرض | ✅ |
| التقارير `/reports` | GET | عرض + تصدير | ✅ |
| KYC `/kyc` | GET | عرض | ✅ |
| المنظمات `/organizations` | GET+POST | عرض + إنشاء | ✅ |
| البضائع الخطرة `/dg` | GET | عرض | ✅ |
| المخاطر `/risk` | GET | عرض | ✅ |

### G.10 شاشات Phase 2

| الشاشة | Route GET | Route POST | الحالة |
|--------|-----------|------------|--------|
| الحاويات `/containers` | ❌ أعمدة خاطئة | ❌ Route مفقود | ❌ BUG-01+02 |
| الجمارك `/customs` | ❌ 500 SQL Error | ❌ أعمدة خاطئة | ❌ BUG-03+04 |
| السائقين `/drivers` | ⚠️ أعمدة خاطئة | — (لا يوجد) | ⚠️ BUG-10 |
| المطالبات `/claims` | ⚠️ أعمدة خاطئة | ❌ أعمدة خاطئة | ❌ BUG-11 |
| السفن `/vessels` | ❌ أعمدة خاطئة | ❌ Route مفقود | ❌ BUG-05+06 |
| الجداول `/schedules` | ❌ أعمدة خاطئة | — (لا يوجد) | ❌ BUG-07 |
| الفروع `/branches` | ✅ | — | ✅ |
| الشركات `/companies` | ✅ | — | ✅ |
| أكواد HS `/hscodes` | ✅ | — | ✅ |

---

## 🎯 H) القسم 6: مرجع كامل لأخطاء أسماء الأعمدة

### H.1 PageController@containers
```php
// ❌ الكود الحالي:                    // ✅ الصحيح:
$c->iso_code                            → $c->size
$c->port                                → $c->location
$c->vessel?->name                       → $c->vesselSchedule?->vessel?->vessel_name
$c->eta                                 → $c->vesselSchedule?->eta?->format('Y-m-d')
```

### H.2 PageController@customs
```php
$c->status                              → $c->customs_status
$c->type                                → $c->declaration_type
$c->duties_amount                       → $c->duty_amount
// Statistics queries:
where('status', 'pending')              → where('customs_status', 'draft')
where('status', 'cleared')              → where('customs_status', 'cleared')
where('status', 'held')                 → where('customs_status', 'held')
```

### H.3 PageController@vessels
```php
$v->name                                → $v->vessel_name
$v->capacity                            → $v->capacity_teu
$v->route                               → $v->operator
```

### H.4 PageController@schedules
```php
$s->vessel?->name                       → $s->vessel?->vessel_name
$s->route                               → $s->service_route
$s->departure_date                      → $s->etd?->format('Y-m-d')
$s->arrival_date                        → $s->eta?->format('Y-m-d')
```

### H.5 PageController@drivers
```php
$d->plate_number                        → $d->vehicle_plate
$d->deliveries_count                    → $d->total_deliveries
$d->zone                                → implode(', ', $d->zones ?? [])
```

### H.6 PageController@claims + claimsStore
```php
$c->type                                → $c->claim_type
$c->amount                              → $c->claimed_amount
$c->customer_name                       → $c->shipment?->recipient_name  // أو $c->filer?->name
// claimsStore:
'type' => ...                           → 'claim_type' => ...
'amount' => ...                         → 'claimed_amount' => ...
'customer_name' => ...                  → حذفه — استخدم 'filed_by' => auth()->id()
```

### H.7 PageController@customsStore
```php
'type' => ...                           → 'declaration_type' => ...
'status' => 'pending'                   → 'customs_status' => 'draft'
'currency' => 'SAR'                     → 'declared_currency' => 'SAR'
```

### H.8 PageController@containersStore
```php
'iso_code' => ...                       → 'size' => ...
'port' => ...                           → 'location' => ...
```

### H.9 DashboardController@index
```php
$wallet?->balance                       → $wallet?->available_balance
```

---

## 📈 I) القسم 7: توصيات الإصلاح بالأولوية

### 🔴 أولوية 1 — يجب قبل أي استخدام production

| # | الإصلاح | الملفات | الجهد |
|---|---------|---------|-------|
| 1 | إصلاح 6 methods في PageController بالأعمدة الصحيحة | `PageController.php` | 1 ساعة |
| 2 | إضافة `POST /containers` و `POST /vessels` routes | `web.php` | 5 دقائق |
| 3 | إضافة `vesselsStore()` method | `PageController.php` | 15 دقيقة |
| 4 | إصلاح `containersStore()` و `customsStore()` و `claimsStore()` | `PageController.php` | 30 دقيقة |
| 5 | إنشاء Blade forms: containers, customs, claims, vessels | `resources/views/pages/*/partials/` | 30 دقيقة |
| 6 | إصلاح `DashboardController` → `available_balance` | `DashboardController.php` | 2 دقيقة |
| 7 | إصلاح `PageController@tracking` → حذف `carrier_tracking_number` | `PageController.php` | 5 دقائق |

### 🔒 أولوية 2 — أمان (يجب قبل production)

| # | الإصلاح | الجهد |
|---|---------|-------|
| 8 | إضافة account_id check في `UserWebController@toggle` و `@destroy` | 10 دقائق |
| 9 | إضافة `throttle:5,1` middleware على Web login route | 2 دقيقة |
| 10 | إضافة ledger entry في `WalletWebController@hold` | 15 دقيقة |
| 11 | إبطال جلسة المستخدم عند toggle status → suspended | 20 دقيقة |

### 🟡 أولوية 3 — تحسين (بعد production)

| # | الإصلاح |
|---|---------|
| 12 | إضافة RBAC middleware (`CheckPermission`) على كل web routes |
| 13 | إضافة `@can` directives في Blade لإخفاء أزرار بلا صلاحية |
| 14 | تنفيذ حقيقي لـ Store sync و test |
| 15 | إصلاح عدادات Sidebar لتكون tenant-scoped |
| 16 | إضافة `GET /shipments/create` route إذا كانت هناك صفحة مستقلة |

---

## ✅ J) القسم 8: ما يعمل بشكل ممتاز

- ✅ المصادقة (login/logout) مع session regeneration
- ✅ Tenant isolation عبر BelongsToAccount + AccountScope (يعمل على ~90% من النماذج)
- ✅ CSRF protection شاملة على كل Forms
- ✅ Input validation على كل store/update methods
- ✅ SQL injection protection (Eloquent ORM)
- ✅ Mass assignment protection ($fillable على كل 126 model)
- ✅ الشحنات: create → show → cancel → return → label → export (سلسلة كاملة)
- ✅ الطلبات: create → ship → cancel
- ✅ المحفظة: topup مع ledger entry
- ✅ الدعم الفني: create → reply → resolve (سلسلة كاملة)
- ✅ المستخدمين: create → toggle → delete
- ✅ الإشعارات: read single → read all
- ✅ العناوين: create → default → delete
- ✅ التسعير، الأدوار، الدعوات، المنظمات، الإعدادات
- ✅ API routes مع rate limiting + sanctum auth
- ✅ سجل التدقيق + التقارير + التصدير

---

## 📊 K) معايير النجاح (من المواصفات)

| المعيار | النتيجة |
|---------|---------|
| 0 Dead Buttons | ❌ **3** dead buttons (stores sync, test, orders sync) |
| 0 Method mismatch (405) | ❌ **2** (POST containers, POST vessels) |
| 0 Cross-tenant leak | ❌ **1** (UserWebController toggle/destroy) |
| 0 unauthorized access success | ❌ **لا يوجد RBAC** — كل مستخدم مصادق يصل لكل شيء |
| كل عمليات المالية لها Ledger أثر | ❌ **1** (wallet hold بلا ledger) |
| كل العمليات تعمل من الواجهة إلى DB | ❌ **12** عملية لا تعمل |

### **الحكم النهائي: ❌ غير جاهز — يتطلب إصلاح 12 خطأ حرج + 8 ثغرات أمنية**
