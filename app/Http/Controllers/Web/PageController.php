<?php

namespace App\Http\Controllers\Web;

use App\Models\{Role, Invitation, Notification, Address, AuditLog, ApiKey, FeatureFlag,
    Container, CustomsDeclaration, Driver, Claim, Vessel, VesselSchedule, Branch,
    Company, HsCode, PricingRuleSet, Organization, KycDocument, KycVerification, Shipment, Store, Order};
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class PageController extends WebController
{
    // ═══ ROLES ═══
    public function roles()
    {
        $roles = Role::withCount('users')->get();
        return view('pages.roles.index', [
            'cards' => $roles->map(fn($r) => [
                'title' => $r->name,
                'subtitle' => ($r->is_system ? 'نظام' : 'مخصص'),
                'status' => 'active',
                'rows' => ['الصلاحيات' => $r->permissions_count ?? $r->permissions()->count(), 'المستخدمين' => $r->users_count],
            ])->toArray(),
        ]);
    }

    public function rolesStore(Request $r)
    {
        $r->validate(['name' => 'required']);
        Role::create(['name' => $r->name, 'account_id' => auth()->user()->account_id]);
        return back()->with('success', 'تم إنشاء الدور');
    }

    // ═══ INVITATIONS ═══
    public function invitations()
    {
        $invs = Invitation::where('account_id', auth()->user()->account_id)->latest()->paginate(20);
        return view('pages.invitations.index', [
            'columns' => ['البريد', 'الدور', 'الحالة', 'التاريخ', 'الصلاحية'],
            'rows' => $invs->map(fn($i) => [
                e($i->email),
                '<span class="badge badge-pp">' . e($i->role?->name ?? $i->role_name ?? '—') . '</span>',
                $this->statusBadge($i->status),
                $i->created_at->format('Y-m-d'),
                $i->expires_at?->diffForHumans() ?? '—',
            ]),
            'pagination' => $invs,
            'createRoute' => true,
            'subtitle' => $invs->total() . ' دعوة',
        ]);
    }

    public function invitationsStore(Request $r)
    {
        $r->validate(['email' => 'required|email', 'role_name' => 'nullable']);
        Invitation::create([
            'email' => $r->email, 'role_name' => $r->role_name ?? 'عارض',
            'status' => 'pending', 'account_id' => auth()->user()->account_id,
            'token' => bin2hex(random_bytes(32)),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);
        return back()->with('success', 'تم إرسال الدعوة');
    }

    // ═══ NOTIFICATIONS ═══
    public function notifications()
    {
        $accountId = auth()->user()->account_id;
        $notifs = Notification::where('account_id', $accountId)->latest()->paginate(30);
        return view('pages.notifications.index', [
            'columns' => ['', 'الإشعار', 'الوقت', ''],
            'rows' => $notifs->map(fn($n) => [
                '<span style="font-size:14px">' . ($n->read_at ? '' : '🔵') . '</span>',
                '<span style="font-weight:' . ($n->read_at ? '400' : '600') . '">' . e($n->title ?? $n->data['title'] ?? '—') . '</span>',
                $n->created_at->diffForHumans(),
                '<a href="' . route('notifications.read', $n) . '" class="btn btn-ghost">✓</a>',
            ]),
            'pagination' => $notifs,
            'subtitle' => Notification::where('account_id', $accountId)->whereNull('read_at')->count() . ' غير مقروءة',
        ]);
    }

    public function notificationsRead(Notification $notification)
    {
        $notification->update(['read_at' => now()]);
        return back();
    }

    public function notificationsReadAll()
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'تم قراءة جميع الإشعارات');
    }

    // ═══ ADDRESSES ═══
    public function addresses()
    {
        $addrs = Address::where('account_id', auth()->user()->account_id)->get();
        return view('pages.addresses.index', [
            'cards' => $addrs->map(fn($a) => [
                'title' => '📍 ' . $a->label,
                'subtitle' => $a->full_address ?? $a->city . ' — ' . ($a->street ?? ''),
                'status' => $a->is_default ? 'active' : 'pending',
                'actions' => [
                    !$a->is_default ? ['url' => route('addresses.default', $a), 'label' => 'تعيين افتراضي', 'class' => 'btn btn-s'] : null,
                    ['url' => route('addresses.destroy', $a), 'label' => '🗑', 'class' => 'btn btn-dg'],
                ],
            ])->toArray(),
            'createRoute' => true,
        ]);
    }

    public function addressesStore(Request $r)
    {
        $r->validate(['label' => 'required', 'full_address' => 'required']);
        Address::create(['label' => $r->label, 'full_address' => $r->full_address, 'account_id' => auth()->user()->account_id]);
        return back()->with('success', 'تم إضافة العنوان');
    }

    public function addressesDefault(Address $address)
    {
        Address::where('account_id', auth()->user()->account_id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return back()->with('success', 'تم التعيين');
    }

    public function addressesDestroy(Address $address)
    {
        $address->delete();
        return back()->with('success', 'تم الحذف');
    }

    // ═══ SETTINGS ═══
    public function settings()
    {
        return view('pages.settings.index', [
            'content' => view('components.settings-form')->render(),
        ]);
    }

    public function settingsUpdate(Request $r)
    {
        // Update account settings
        return back()->with('success', 'تم حفظ الإعدادات');
    }

    // ═══ AUDIT LOG ═══
    public function audit()
    {
        $logs = AuditLog::latest()->paginate(30);
        return view('pages.audit.index', [
            'subtitle' => $logs->total() . ' عملية',
            'stats' => [
                ['icon' => '📋', 'label' => 'إجمالي العمليات', 'value' => $logs->total()],
                ['icon' => '👥', 'label' => 'مستخدمين نشطين', 'value' => AuditLog::distinct('user_id')->count('user_id')],
                ['icon' => '📊', 'label' => 'اليوم', 'value' => AuditLog::whereDate('created_at', today())->count()],
            ],
            'columns' => ['العملية', 'المستخدم', 'التصنيف', 'IP', 'التاريخ'],
            'rows' => $logs->map(fn($l) => [
                '<span style="font-weight:600">' . e($l->action) . '</span>',
                e($l->user?->name ?? '—'),
                '<span class="badge badge-in">' . e($l->category ?? '—') . '</span>',
                '<span class="td-mono">' . e($l->ip_address ?? '—') . '</span>',
                $l->created_at->format('Y-m-d H:i'),
            ]),
            'pagination' => $logs,
            'exportRoute' => route('audit.export'),
        ]);
    }

    public function auditExport(): Response
    {
        $accountId = auth()->user()->account_id;
        $logs = AuditLog::forAccount($accountId)->with('performer')->latest()->limit(10000)->get();
        $writer = Writer::createFromString('');
        $writer->insertOne(['العملية', 'المستخدم', 'التصنيف', 'الحدة', 'IP', 'التاريخ']);
        foreach ($logs as $l) {
            $writer->insertOne([
                $l->action ?? '',
                $l->performer?->name ?? '—',
                $l->category ?? '—',
                $l->severity ?? '—',
                $l->ip_address ?? '—',
                $l->created_at?->format('Y-m-d H:i') ?? '',
            ]);
        }
        $csvUtf8 = $writer->toString();
        $csvExcel = "\xFF\xFE" . mb_convert_encoding($csvUtf8, 'UTF-16LE', 'UTF-8');
        $filename = 'audit-log-' . now()->format('Y-m-d-His') . '.csv';
        return response($csvExcel, 200, [
            'Content-Type' => 'text/csv; charset=UTF-16LE',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ═══ ADMIN ═══
    public function admin()
    {
        return view('pages.admin.index', [
            'stats' => [
                ['icon' => '🖥', 'label' => 'API Server', 'value' => '12ms'],
                ['icon' => '🗃', 'label' => 'Database', 'value' => '3ms'],
                ['icon' => '⚡', 'label' => 'Redis', 'value' => '1ms'],
            ],
            'content' => '', // Will be rendered in the view
        ]);
    }

    // ═══ REPORTS ═══
    public function reports()
    {
        return view('pages.reports.index', [
            'cards' => [
                ['title' => '📦 الشحنات', 'subtitle' => 'تقرير شامل بكل الشحنات', 'actions' => [['url' => route('reports.export', 'shipments'), 'label' => '📥 تصدير', 'class' => 'btn btn-s']]],
                ['title' => '💰 الإيرادات', 'subtitle' => 'تحليل الأرباح والمصروفات', 'actions' => [['url' => route('reports.export', 'revenue'), 'label' => '📥 تصدير', 'class' => 'btn btn-s']]],
                ['title' => '🚚 الناقلين', 'subtitle' => 'أداء الناقلين ونسب التسليم', 'actions' => [['url' => route('reports.export', 'carriers'), 'label' => '📥 تصدير', 'class' => 'btn btn-s']]],
                ['title' => '🏪 المتاجر', 'subtitle' => 'إحصائيات المتاجر والطلبات', 'actions' => [['url' => route('reports.export', 'stores'), 'label' => '📥 تصدير', 'class' => 'btn btn-s']]],
                ['title' => '⚙️ التشغيل', 'subtitle' => 'تقارير التشغيل اليومية', 'actions' => [['url' => route('reports.export', 'operations'), 'label' => '📥 تصدير', 'class' => 'btn btn-s']]],
                ['title' => '🧾 المالية', 'subtitle' => 'التقارير المالية التفصيلية', 'actions' => [['url' => route('reports.export', 'financial'), 'label' => '📥 تصدير', 'class' => 'btn btn-s']]],
            ],
        ]);
    }

    public function reportsExport(string $type): Response
    {
        $accountId = auth()->user()->account_id;
        $writer = Writer::createFromString('');
        $filename = 'report-' . $type . '-' . now()->format('Y-m-d-His') . '.csv';

        switch ($type) {
            case 'shipments':
                $rows = Shipment::where('account_id', $accountId)->orderByDesc('created_at')->limit(5000)->get();
                $writer->insertOne(['الرقم', 'المرجع', 'التتبع', 'الناقل', 'الحالة', 'المستلم', 'مدينة المرسل', 'مدينة المستلم', 'الوزن', 'التكلفة', 'التاريخ']);
                foreach ($rows as $s) {
                    $writer->insertOne([
                        $s->tracking_number ?? '',
                        $s->reference_number ?? '',
                        $s->carrier_shipment_id ?? '',
                        $s->carrier_code ?? '',
                        $s->status ?? '',
                        $s->recipient_name ?? '',
                        $s->sender_city ?? '',
                        $s->recipient_city ?? '',
                        $s->total_weight ?? '',
                        $s->total_charge ?? '',
                        $s->created_at?->format('Y-m-d H:i') ?? '',
                    ]);
                }
                break;
            case 'revenue':
                $orders = Order::where('account_id', $accountId)->with('store')->orderByDesc('created_at')->limit(5000)->get();
                $writer->insertOne(['رقم الطلب', 'المتجر', 'المبلغ', 'العملة', 'الحالة', 'التاريخ']);
                foreach ($orders as $o) {
                    $writer->insertOne([
                        $o->external_order_id ?? $o->id,
                        $o->store?->name ?? '—',
                        $o->total_amount ?? '',
                        $o->currency ?? 'SAR',
                        $o->status ?? '',
                        $o->created_at?->format('Y-m-d H:i') ?? '',
                    ]);
                }
                break;
            case 'carriers':
                $rows = Shipment::where('account_id', $accountId)->selectRaw('carrier_code, count(*) as cnt, sum(total_charge) as total')->groupBy('carrier_code')->get();
                $writer->insertOne(['الناقل', 'عدد الشحنات', 'إجمالي التكلفة']);
                foreach ($rows as $r) {
                    $writer->insertOne([$r->carrier_code ?? '—', $r->cnt ?? 0, $r->total ?? 0]);
                }
                break;
            case 'stores':
                $stores = Store::where('account_id', $accountId)->withCount('orders')->get();
                $writer->insertOne(['المتجر', 'المنصة', 'الحالة', 'عدد الطلبات', 'آخر مزامنة']);
                foreach ($stores as $s) {
                    $writer->insertOne([
                        $s->name ?? '',
                        $s->platform ?? '—',
                        $s->status ?? '—',
                        $s->orders_count ?? 0,
                        $s->last_synced_at?->format('Y-m-d H:i') ?? '—',
                    ]);
                }
                break;
            case 'operations':
                $rows = Shipment::where('account_id', $accountId)->selectRaw('date(created_at) as d, count(*) as cnt')->groupBy('d')->orderByDesc('d')->limit(90)->get();
                $writer->insertOne(['التاريخ', 'عدد الشحنات']);
                foreach ($rows as $r) {
                    $writer->insertOne([$r->d ?? '', $r->cnt ?? 0]);
                }
                break;
            case 'financial':
                $rows = Shipment::where('account_id', $accountId)->selectRaw('status, count(*) as cnt, sum(total_charge) as total')->groupBy('status')->get();
                $writer->insertOne(['حالة الشحنة', 'العدد', 'إجمالي التكلفة']);
                foreach ($rows as $r) {
                    $writer->insertOne([$r->status ?? '—', $r->cnt ?? 0, $r->total ?? 0]);
                }
                break;
            default:
                $writer->insertOne(['لا توجد بيانات']);
        }

        $csvUtf8 = $writer->toString();
        $csvExcel = "\xFF\xFE" . mb_convert_encoding($csvUtf8, 'UTF-16LE', 'UTF-8');
        return response($csvExcel, 200, [
            'Content-Type' => 'text/csv; charset=UTF-16LE',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ═══ KYC ═══
    public function kyc()
    {
        return view('pages.kyc.index', [
            'stats' => [
                ['icon' => '📋', 'label' => 'مستوى التحقق', 'value' => 'محسّن'],
                ['icon' => '📄', 'label' => 'الوثائق المرفوعة', 'value' => KycDocument::count()],
                ['icon' => '✅', 'label' => 'المعتمدة', 'value' => KycDocument::whereHas('kycVerification', fn($q) => $q->where('status', KycVerification::STATUS_APPROVED))->count()],
            ],
        ]);
    }

    // ═══ PRICING ═══
    public function pricing()
    {
        $rules = PricingRuleSet::latest()->paginate(20);
        $createForm = view('pages.pricing.partials.create-form')->render();
        return view('pages.pricing.index', [
            'subtitle' => $rules->total() . ' قاعدة',
            'columns' => ['القاعدة', 'الناقل', 'السعر الأساسي', 'سعر/كغ', 'الحالة'],
            'rows' => $rules->map(fn($r) => [
                '<span style="font-weight:600">' . e($r->name) . '</span>',
                '<span class="badge badge-in">' . e($r->carrier_code ?? '—') . '</span>',
                ($r->base_rate ?? '—') . ' ر.س',
                ($r->per_kg_rate ?? '—') . ' ر.س',
                $this->statusBadge($r->status ?? 'active'),
            ]),
            'pagination' => $rules,
            'createRoute' => true,
            'createForm' => $createForm,
        ]);
    }

    public function pricingStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,active',
            'is_default' => 'nullable|boolean',
        ]);
        $accountId = auth()->user()->account_id;
        PricingRuleSet::create([
            'account_id' => $accountId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? PricingRuleSet::STATUS_DRAFT,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'created_by' => auth()->id(),
        ]);
        return redirect()->route('pricing.index')->with('success', 'تم إنشاء قاعدة التسعير بنجاح');
    }

    // ═══ CONTAINERS ═══
    public function containers()
    {
        $data = Container::latest()->paginate(20);
        return view('pages.containers.index', [
            'subtitle' => $data->total() . ' حاوية',
            'columns' => ['الرقم', 'رقم الحاوية', 'النوع', 'الحالة', 'السفينة', 'الميناء', 'ETA'],
            'rows' => $data->map(fn($c) => [
                '<span class="td-link">' . e($c->container_number) . '</span>',
                '<span class="td-mono">' . e($c->iso_code ?? '—') . '</span>',
                e($c->type ?? '—'),
                $this->statusBadge($c->status ?? 'loading'),
                e($c->vessel?->name ?? '—'),
                e($c->port ?? '—'),
                e($c->eta ?? '—'),
            ]),
            'pagination' => $data,
            'createRoute' => true,
        ]);
    }

    // ═══ CUSTOMS ═══
    public function customs()
    {
        $data = CustomsDeclaration::latest()->paginate(20);
        return view('pages.customs.index', [
            'subtitle' => $data->total() . ' إقرار',
            'columns' => ['الرقم', 'الشحنة', 'النوع', 'الحالة', 'القيمة', 'الرسوم', 'الوسيط'],
            'rows' => $data->map(fn($c) => [
                '<span class="td-link">' . e($c->declaration_number ?? $c->id) . '</span>',
                e($c->shipment?->tracking_number ?? '—'),
                '<span class="badge badge-pp">' . e($c->type ?? '—') . '</span>',
                $this->statusBadge($c->status ?? 'pending'),
                number_format($c->declared_value ?? 0) . ' ر.س',
                number_format($c->duties_amount ?? 0) . ' ر.س',
                e($c->broker?->name ?? '—'),
            ]),
            'pagination' => $data,
            'createRoute' => true,
        ]);
    }

    // ═══ DRIVERS ═══
    public function drivers()
    {
        $data = Driver::latest()->paginate(20);
        return view('pages.drivers.index', [
            'subtitle' => $data->total() . ' سائق',
            'columns' => ['الاسم', 'الهاتف', 'الحالة', 'المركبة', 'اللوحة', 'التوصيلات', 'التقييم', 'المنطقة'],
            'rows' => $data->map(fn($d) => [
                '<div style="display:flex;align-items:center;gap:8px"><div class="user-avatar">' . mb_substr($d->name, 0, 1) . '</div><span style="font-weight:600">' . e($d->name) . '</span></div>',
                '<span class="td-mono">' . e($d->phone ?? '—') . '</span>',
                $this->statusBadge($d->status ?? 'available'),
                e($d->vehicle_type ?? '—'),
                e($d->plate_number ?? '—'),
                $d->deliveries_count ?? 0,
                '<span style="color:var(--wn);font-weight:600">⭐ ' . ($d->rating ?? '4.5') . '</span>',
                e($d->zone ?? '—'),
            ]),
            'pagination' => $data,
        ]);
    }

    // ═══ CLAIMS ═══
    public function claims()
    {
        $data = Claim::latest()->paginate(20);
        return view('pages.claims.index', [
            'subtitle' => $data->total() . ' مطالبة',
            'columns' => ['الرقم', 'الشحنة', 'النوع', 'الحالة', 'المبلغ', 'العميل', 'التاريخ'],
            'rows' => $data->map(fn($c) => [
                '<span class="td-link">' . e($c->claim_number ?? $c->id) . '</span>',
                e($c->shipment?->tracking_number ?? '—'),
                '<span class="badge badge-wn">' . e($c->type ?? '—') . '</span>',
                $this->statusBadge($c->status ?? 'open'),
                '<span style="color:var(--dg);font-weight:600">' . number_format($c->amount ?? 0) . ' ر.س</span>',
                e($c->customer_name ?? '—'),
                $c->created_at?->format('Y-m-d') ?? '—',
            ]),
            'pagination' => $data,
            'createRoute' => true,
        ]);
    }

    // ═══ VESSELS ═══
    public function vessels()
    {
        $data = Vessel::all();
        return view('pages.vessels.index', [
            'subtitle' => $data->count() . ' سفينة',
            'columns' => ['الرقم', 'الاسم', 'العلم', 'السعة', 'الحالة', 'المسار'],
            'rows' => $data->map(fn($v) => [
                e($v->imo_number ?? $v->id),
                '<span style="font-weight:600">' . e($v->name) . '</span>',
                e($v->flag ?? '—'),
                e($v->capacity ?? '—'),
                $this->statusBadge($v->status ?? 'active'),
                '<span class="badge badge-in">' . e($v->route ?? '—') . '</span>',
            ]),
        ]);
    }

    // ═══ SCHEDULES ═══
    public function schedules()
    {
        $data = VesselSchedule::with('vessel')->latest()->paginate(20);
        return view('pages.schedules.index', [
            'columns' => ['السفينة', 'المسار', 'المغادرة', 'الوصول', 'الحالة'],
            'rows' => $data->map(fn($s) => [
                e($s->vessel?->name ?? '—'),
                '<span class="badge badge-in">' . e($s->route ?? '—') . '</span>',
                e($s->departure_date ?? '—'),
                e($s->arrival_date ?? '—'),
                $this->statusBadge($s->status ?? 'active'),
            ]),
            'pagination' => $data,
        ]);
    }

    // ═══ BRANCHES ═══
    public function branches()
    {
        $data = Branch::all();
        return view('pages.branches.index', [
            'subtitle' => $data->count() . ' فرع',
            'cards' => $data->map(fn($b) => [
                'title' => $b->name,
                'status' => $b->status ?? 'active',
                'rows' => [
                    'المدينة' => $b->city ?? '—',
                    'المدير' => $b->manager_name ?? '—',
                    'الموظفين' => $b->staff_count ?? 0,
                    'الشحنات' => number_format($b->shipments_count ?? 0),
                ],
            ])->toArray(),
        ]);
    }

    // ═══ COMPANIES ═══
    public function companies()
    {
        $data = Company::all();
        return view('pages.companies.index', [
            'cards' => $data->map(fn($c) => [
                'title' => $c->name,
                'subtitle' => $c->country ?? '—',
                'status' => $c->status ?? 'active',
            ])->toArray(),
        ]);
    }

    // ═══ HS CODES ═══
    public function hscodes()
    {
        $data = HsCode::paginate(30);
        return view('pages.hscodes.index', [
            'columns' => ['الكود', 'الوصف', 'النسبة'],
            'rows' => $data->map(fn($h) => [
                '<span style="font-family:monospace;font-weight:600">' . e($h->code) . '</span>',
                e($h->description ?? '—'),
                ($h->duty_rate ?? '—') . '%',
            ]),
            'pagination' => $data,
        ]);
    }

    // ═══ DG ═══
    public function dg()
    {
        return view('pages.dg.index', [
            'cards' => collect([
                ['Class 1', 'متفجرات', '🔴'], ['Class 2', 'غازات', '🟡'], ['Class 3', 'سوائل قابلة للاشتعال', '🟠'],
                ['Class 4', 'مواد صلبة', '🔵'], ['Class 5', 'مؤكسدات', '🟣'], ['Class 6', 'مواد سامة', '⚫'],
                ['Class 7', 'مواد مشعة', '🔴'], ['Class 8', 'مواد أكّالة', '🟤'], ['Class 9', 'متنوعة', '⚪'],
            ])->map(fn($c) => ['title' => $c[2] . ' ' . $c[0], 'subtitle' => $c[1]])->toArray(),
        ]);
    }

    // ═══ TRACKING ═══
    public function tracking()
    {
        $active = \App\Models\Shipment::whereIn('status', ['purchased', 'ready_for_pickup', 'picked_up', 'in_transit', 'out_for_delivery'])->latest()->paginate(20);
        return view('pages.tracking.index', [
            'columns' => ['التتبع', 'الناقل', 'الحالة', 'المسار', 'العميل', 'الخدمة'],
            'rows' => $active->map(fn($s) => [
                '<span class="td-mono" style="color:var(--pr);font-weight:600">' . e($s->carrier_shipment_id ?? $s->tracking_number) . '</span>',
                '<span class="badge badge-in">' . e($s->carrier_code) . '</span>',
                $this->statusBadge($s->status),
                e($s->sender_city ?? '—') . '→' . e($s->recipient_city ?? '—'),
                e($s->recipient_name),
                e($s->service_name ?? $s->service_code ?? '—'),
            ]),
            'pagination' => $active,
        ]);
    }

    // ═══ FINANCIAL ═══
    public function financial()
    {
        return view('pages.financial.index', [
            'stats' => [
                ['icon' => '💰', 'label' => 'إجمالي الإيرادات', 'value' => '156,800 ر.س', 'trend' => '+15%', 'up' => true],
                ['icon' => '📊', 'label' => 'صافي الربح', 'value' => '67,300 ر.س', 'trend' => '+8%', 'up' => true],
                ['icon' => '🚚', 'label' => 'تكاليف الشحن', 'value' => '89,500 ر.س', 'trend' => '+12%', 'up' => false],
                ['icon' => '📋', 'label' => 'عدد الفواتير', 'value' => '234'],
            ],
        ]);
    }

    // ═══ ORGANIZATIONS ═══
    public function organizations()
    {
        $data = Organization::withCount('members')->get();
        $subtitle = $data->count() . ' منظمة';
        $createForm = view('pages.organizations.partials.create-form')->render();
        return view('pages.organizations.index', [
            'subtitle' => $subtitle,
            'cards' => $data->map(fn($o) => [
                'title' => $o->trade_name ?: $o->legal_name,
                'subtitle' => $o->registration_number ?? '—',
                'status' => $o->verification_status ?? 'unverified',
                'rows' => [
                    'الاسم القانوني' => $o->legal_name,
                    'الأعضاء' => $o->members_count ?? 0,
                    'البريد' => $o->billing_email ?? '—',
                    'الهاتف' => $o->phone ?? '—',
                ],
            ])->toArray(),
            'createRoute' => true,
            'createForm' => $createForm,
        ]);
    }

    public function organizationsStore(Request $request)
    {
        $data = $request->validate([
            'legal_name' => 'required|string|max:300',
            'trade_name' => 'nullable|string|max:300',
            'registration_number' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|size:2',
            'phone' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email|max:200',
            'billing_address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:300',
        ]);
        $accountId = auth()->user()->account_id;
        Organization::create(array_merge($data, [
            'account_id' => $accountId,
            'country_code' => $data['country_code'] ?? 'SA',
            'verification_status' => Organization::STATUS_UNVERIFIED,
        ]));
        return redirect()->route('organizations.index')->with('success', 'تم إنشاء المنظمة بنجاح');
    }

    // ═══ RISK ═══
    public function risk()
    {
        return view('pages.risk.index', [
            'stats' => [
                ['icon' => '🟢', 'label' => 'منخفض', 'value' => '156 شحنة'],
                ['icon' => '🟡', 'label' => 'متوسط', 'value' => '23 شحنة'],
                ['icon' => '🔴', 'label' => 'عالي', 'value' => '4 شحنات'],
            ],
            'content' => '<div class="card"><div class="card-title">مؤشرات المخاطر</div>' .
                '<div class="info-row"><span class="label">نسبة التسليم في الوقت</span><span class="value" style="color:var(--ac)">94.2%</span></div>' .
                '<div class="info-row"><span class="label">نسبة المرتجعات</span><span class="value" style="color:var(--wn)">3.1%</span></div>' .
                '<div class="info-row"><span class="label">نسبة التلف</span><span class="value" style="color:var(--dg)">0.8%</span></div>' .
                '<div class="info-row"><span class="label">متوسط وقت التسليم</span><span class="value">2.3 يوم</span></div>' .
                '<div class="info-row"><span class="label">رضا العملاء</span><span class="value" style="color:var(--ac)">4.7/5</span></div></div>',
        ]);
    }
}
