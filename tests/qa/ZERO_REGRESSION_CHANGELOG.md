# Zero-Regression Enforcement — قائمة التعديلات

**التاريخ:** 2026-02-24  
**المرجع:** تقرير Shipping Gateway QA Audit (2026-02-24)

---

## 1️⃣ قائمة التعديلات (الملف ← التغيير ← السبب)

| الملف | التغيير | السبب |
|-------|---------|--------|
| **PageController.php** | `drivers()`: استخدام `vehicle_plate`, `total_deliveries`, `zones` بدل `plate_number`, `deliveries_count`, `zone` | BUG-10: أعمدة خاطئة — التوافق مع migration |
| **PageController.php** | `claims()`: استخدام `claim_type`, `claimed_amount`, وعرض العميل من `shipment?->recipient_name` | BUG-11: أعمدة خاطئة ولا يوجد `customer_name` |
| **PageController.php** | `claimsStore()`: التحقق والإدخال بـ `claim_type`, `claimed_amount`, `filed_by`, `incident_date`؛ قبول `type`/`amount` للتوافق مع النماذج القديمة | BUG-11 + توافق رجعي |
| **PageController.php** | `containers()`: تمرير `containers`, إحصائيات، وتحميل `vesselSchedule.vessel` و `vessel`؛ نطاق `account_id` عند وجود العمود | BUG-01 + دعم عرض الحاويات في الـ view |
| **PageController.php** | `containersStore()`: إضافة `account_id` فقط عند وجود العمود (Schema::hasColumn) | توافق مع جدول بدون account_id |
| **Container.php** | إضافة علاقة `vesselSchedule()` لجدول يحتوي `vessel_schedule_id` | دعم عرض السفينة في قائمة الحاويات |
| **containers/index.blade.php** | عرض: `vesselSchedule?->vessel?->vessel_name ?? vessel?->vessel_name ?? vessel?->name`، و`location` بدل origin/destination_port؛ خريطة حالات phase2؛ زر الإضافة → `route('containers.store')` | BUG-01, BUG-02: أعمدة صحيحة + إصلاح 405 |
| **routes/web.php** | إضافة `->middleware('throttle:5,1')` على `POST /login` | SEC-03: حد معدل الطلبات على تسجيل الدخول Web |
| **WalletWebController.php** | `hold()`: تنفيذ داخل `DB::transaction`، إنشاء `WalletLedgerEntry` نوع `lock` مع amount سالب و`running_balance` | WARN-06 / Audit: أثر مالي في الدفتر |
| **UserWebController** | لا تعديل — يوجد بالفعل فحص `account_id` في `toggle()` و `destroy()` | SEC-02 مغلق مسبقاً |
| **DashboardController** | لا تعديل — يستخدم بالفعل `$wallet->available_balance` | BUG-08 مغلق مسبقاً |
| **PageController@tracking** | لا تعديل — يستخدم بالفعل `tracking_number` و `carrier_shipment_id` | BUG-09 مغلق مسبقاً |
| **routes** | لا إضافة — `POST /containers` و `POST /vessels` موجودان مسبقاً | BUG-02/06 مغلقان مسبقاً |

---

## 2️⃣ Confirmation Report

- **12 حشرة حرجة من التقرير:** تم التعامل معها إما بإصلاح في هذا التنفيذ (أعمدة containers/drivers/claims، نموذج الحاويات، wallet hold ledger، throttle) أو كانت مُصلحة مسبقاً (Dashboard balance، tracking، routes، User tenant check).
- **8 ثغرات أمنية مذكورة في التقرير:** تم إغلاق ما يخص هذا النطاق: throttle على login (SEC-03)، وفحص tenant في User (SEC-02) كان موجوداً؛ wallet hold ledger (SEC-08) تمت إضافته.
- **0 Regression:** لم يُحذف أي route أو method يعمل؛ لم تُغيّر أسماء Models/Tables؛ التعديلات إما إصلاح أعمدة أو إضافة تحقق/دفتر فقط.
- **0 Schema Change:** لم يُضف أو يُعدّل أي migration أو عمود في قاعدة البيانات.

---

## 3️⃣ Code Snippets (الأجزاء المُعدّلة فقط)

### PageController — drivers() rows
```php
'rows' => $data->map(fn($d) => [
    // ...
    e($d->vehicle_plate ?? '—'),
    (int) ($d->total_deliveries ?? 0),
    // ...
    e($d->zones ? (is_array($d->zones) ? implode(', ', $d->zones) : (string) $d->zones) : '—'),
]),
```

### PageController — claims() rows
```php
'rows' => $data->map(fn($c) => [
    // ...
    '<span class="badge badge-wn">' . e($c->claim_type ?? '—') . '</span>',
    '<span ...>' . number_format((float) ($c->claimed_amount ?? 0), 2) . ' ر.س</span>',
    e($c->shipment?->recipient_name ?? '—'),
]),
```

### PageController — claimsStore()
```php
$data = $request->validate([
    'claim_type' => 'nullable|in:damage,loss,...',
    'claimed_amount' => 'nullable|numeric|min:0',
    'type' => 'nullable|in:damage,loss,delay,overcharge',
    'amount' => 'nullable|numeric|min:0',
    'description' => 'required|string|max:2000',
    'incident_date' => 'nullable|date',
]);
$claimType = $data['claim_type'] ?? $data['type'] ?? 'damage';
$claimedAmount = (float) ($data['claimed_amount'] ?? $data['amount'] ?? 0);
// ... Claim::create([ 'claim_type' => $claimType, 'claimed_amount' => $claimedAmount, 'filed_by' => auth()->id(), ... ]);
```

### PageController — containers() + containersStore()
- تمرير `containers` مع نطاق `account_id` عند وجود العمود، وإحصائيات.
- `containersStore`: إضافة `account_id` فقط إن وُجد العمود.

### Container.php
```php
public function vesselSchedule(): BelongsTo { return $this->belongsTo(VesselSchedule::class, 'vessel_schedule_id'); }
```

### containers/index.blade.php
- عرض السفينة: `$container->vesselSchedule?->vessel?->vessel_name ?? $container->vessel?->vessel_name ?? $container->vessel?->name ?? '—'`
- الموقع: `$container->location`؛ خريطة الحالات لـ empty/loading/loaded/in_transit/at_port/delivered/returned
- نموذج الإضافة: `action="{{ route('containers.store') }}"`

### routes/web.php
```php
Route::post('/login', [AuthWebController::class, 'login'])->middleware('throttle:5,1');
```

### WalletWebController::hold()
- تنفيذ كامل داخل `DB::transaction`.
- بعد تعديل الرصيد: `WalletLedgerEntry::create([ 'type' => WalletLedgerEntry::TYPE_LOCK, 'amount' => -$amount, 'running_balance' => ..., 'description' => 'حجز مبلغ في المحفظة', ... ])`.

---

*تم تنفيذ التعديلات وفق قيود Zero-Regression وبدون تغيير في Schema أو حذف لسلوك يعمل.*
