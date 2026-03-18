# Shipping Gateway – Zero Regression Patch

**التاريخ:** 2026-02-24  
**المرجع:** QA Audit Report (2026-02-24)  
**الإصدار:** Patch Stability & Security Hardening

---

## 1️⃣ ملخص تنفيذي

تم تنفيذ مجموعة إصلاحات حرجة وأمنية وفق مبدأ:

**Zero Regression Enforcement — No Schema Change — Backward Compatible**

الهدف من هذه الدفعة:
- إغلاق أخطاء الأعمدة غير المتطابقة
- منع أخطاء 405
- سد ثغرات أمنية محددة
- ضمان أثر مالي كامل لعمليات المحفظة
- الحفاظ الكامل على السلوك القائم

لا يوجد أي تعديل على:
- Database schema
- أسماء الجداول
- أسماء الـ models
- سلوك يعمل مسبقاً

---

## 2️⃣ ما تم إصلاحه فعلياً في هذه الدفعة

### 🔧 A) إصلاح أعمدة غير متطابقة (Column Drift)

| الوحدة | الحالة |
|--------|--------|
| Drivers | تم تصحيح الأعمدة لتطابق migration |
| Claims | تم تصحيح claim_type و claimed_amount وإزالة customer_name |
| Containers (عرض) | تم تصحيح location + vesselSchedule |
| Containers (إنشاء) | إصلاح mapping ومنع 405 |
| Tracking | يستخدم أعمدة صحيحة فقط |
| Dashboard | يستخدم available_balance |

---

### 💰 B) إصلاح مالي (Audit Compliance)

**Wallet Hold**
- تنفيذ العملية داخل DB::transaction
- إنشاء Ledger Entry لكل عملية hold
- ضمان الاتساق مع topup behavior
- لا تغيير في سلوك الرصيد الظاهري

---

### 🔐 C) إصلاحات أمنية

| الثغرة | الحالة |
|--------|--------|
| Web Login بدون Rate Limit | تم إضافة throttle:5,1 |
| Cross-tenant user manipulation | يوجد فحص account_id |
| Wallet hold بلا ledger | تم إصلاحه |

---

## 3️⃣ ما لم يتم تعديله (خارج نطاق هذه الدفعة)

لتفادي أي التباس — العناصر التالية لم تُعدّل في هذا الإصدار لأنها كانت تعمل فعلياً أو تحتاج دفعة مستقلة:

| العنصر | الحالة |
|--------|--------|
| Customs module | يحتاج تأكيد منفصل |
| Vessels & Schedules | يحتاج مراجعة إضافية |
| RBAC الكامل على Web Routes | غير مُفعّل بعد |
| Store Sync/Test (Stub) | لم يتم تنفيذه بعد |
| Sidebar tenant counters | لم يُعدّل في هذه الدفعة |

---

## 4️⃣ اختبارات القبول المنفذة

تم التحقق من:
- لا يوجد 405 في containers
- لا يوجد SQL Error في claims/drivers
- login rate limit يعمل
- hold ينشئ ledger entry
- لا يوجد تغيير في سلوك topup
- لا يوجد تعديل schema

---

## 5️⃣ تقييم المخاطر بعد الإصلاح

| المجال | التقييم الحالي |
|--------|-----------------|
| Stability | مستقر في نطاق التعديلات |
| Financial Integrity | متوافق مع audit |
| Tenant Isolation | سليم ضمن النطاق |
| Authorization Layer | يحتاج RBAC مستقبلاً |
| Production Readiness | صالح للإطلاق المشروط |

---

## 6️⃣ حالة الإطلاق

### 🚦 Conditional Go

النظام جاهز للإطلاق بشرط:
- عدم تفعيل Customs/Vessels غير المراجعة
- جدولة دفعة RBAC قبل التوسع المؤسسي

---

## 7️⃣ توصية المرحلة التالية

الدفعة القادمة يجب أن تكون:

**Authorization & Governance Hardening Release**

تشمل:
- Middleware Permission Layer
- Blade @can controls
- Audit extension
- Store sync implementation

---

## 8️⃣ تعريف "Zero Regression" في هذه الدفعة

- ✔ لم يُكسر أي endpoint يعمل
- ✔ لم يُحذف أي route
- ✔ لم يتغير أي contract API
- ✔ لم يُعدّل أي migration
- ✔ لم يتغير أي behavior تجاري قائم

---

**QA Gate Status:** Patch Accepted – Controlled Scope
