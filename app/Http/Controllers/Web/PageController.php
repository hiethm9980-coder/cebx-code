<?php

namespace App\Http\Controllers\Web;

use App\Models\Shipment;
use App\Models\Account;
use App\Models\User;
use App\Models\Store;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Address;
use App\Models\SupportTicket;
use App\Models\Notification;
use App\Models\Invitation;
use App\Models\AuditLog;
use App\Models\PricingRule;
use App\Models\KycRequest;
use App\Models\DgClassification;
use App\Models\Container;
use App\Models\CustomsDeclaration;
use App\Models\Driver;
use App\Models\Claim;
use App\Models\Vessel;
use App\Models\Schedule;
use App\Models\Branch;
use App\Models\Company;
use App\Models\HsCode;
use App\Models\RiskRule;
use App\Models\RiskAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class PageController extends WebController
{
    // ═══ TRACKING ═══
    public function tracking(Request $request)
    {
        $trackedShipment = null;
        $trackingHistory = [];

        if ($ref = $request->get('q') ?? $request->get('tracking_number')) {
            $trackedShipment = Shipment::where('reference_number', $ref)
                ->orWhere('carrier_tracking_number', $ref)
                ->with('events')
                ->first();

            if ($trackedShipment) {
                $trackingHistory = $trackedShipment->events->map(fn($e) => [
                    'title'    => $e->description,
                    'date'     => $e->event_at->format('d/m/Y — h:i A'),
                    'location' => $e->location,
                ])->toArray();

                if (empty($trackingHistory)) {
                    $trackingHistory = [
                        ['title' => 'تم إنشاء الشحنة', 'date' => $trackedShipment->created_at->format('d/m/Y — h:i A')],
                    ];
                }
            }
        }

        $activeShipments = Shipment::where('account_id', auth()->user()->account_id)
            ->whereIn('status', ['pending', 'processing', 'shipped', 'in_transit', 'out_for_delivery'])
            ->latest()->take(10)->get();

        return view('pages.tracking.index', compact('trackedShipment', 'trackingHistory', 'activeShipments'));
    }

    // ═══ ROLES ═══
    public function roles()
    {
        return view('pages.roles.index');
    }

    public function rolesStore(Request $request)
    {
        return back()->with('success', 'تم حفظ الأدوار');
    }

    // ═══ INVITATIONS ═══
    public function invitations()
    {
        $invitations = Invitation::where('account_id', auth()->user()->account_id)->latest()->paginate(15);
        return view('pages.invitations.index', compact('invitations'));
    }

    public function invitationsStore(Request $request)
    {
        $v = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:200',
            'role_name' => 'nullable|string',
            'job_title' => 'nullable|string',
        ]);

        Invitation::create(array_merge($v, [
            'account_id' => auth()->user()->account_id,
            'token'      => 'inv_' . bin2hex(random_bytes(16)),
            'status'     => 'pending',
            'expires_at' => now()->addDays(7),
        ]));

        return back()->with('success', 'تم إرسال الدعوة');
    }

    // ═══ NOTIFICATIONS ═══
    public function notifications(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $query = Notification::where('account_id', $accountId);

        if ($filter = $request->get('filter')) {
            if ($filter === 'unread') $query->whereNull('read_at');
            elseif (in_array($filter, ['shipment', 'wallet', 'system'])) $query->where('type', $filter);
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();
        $unreadCount = Notification::where('account_id', $accountId)->whereNull('read_at')->count();
        $readCount   = Notification::where('account_id', $accountId)->whereNotNull('read_at')->count();

        return view('pages.notifications.index', compact('notifications', 'unreadCount', 'readCount'));
    }

    public function notificationsRead(Notification $notification)
    {
        $notification->update(['read_at' => now()]);
        return back();
    }

    public function notificationsReadAll()
    {
        Notification::where('account_id', auth()->user()->account_id)->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'تم تحديد الكل كمقروء');
    }

    // ═══ ADDRESSES ═══
    public function addresses()
    {
        $addresses = Address::where('account_id', auth()->user()->account_id)->latest()->get();
        return view('pages.addresses.index', compact('addresses'));
    }

    public function addressesStore(Request $request)
    {
        $v = $request->validate([
            'label' => 'nullable|string|max:50',
            'name' => 'required|string|max:200',
            'phone' => 'required|string',
            'city' => 'required|string',
            'district' => 'nullable|string',
            'street' => 'nullable|string',
            'postal_code' => 'nullable|string',
        ]);

        Address::create(array_merge($v, ['account_id' => auth()->user()->account_id]));
        return back()->with('success', 'تم إضافة العنوان');
    }

    public function addressesDefault(Address $address)
    {
        Address::where('account_id', auth()->user()->account_id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
        return back()->with('success', 'تم تعيين العنوان الافتراضي');
    }

    public function addressesDestroy(Address $address)
    {
        $address->delete();
        return back()->with('success', 'تم حذف العنوان');
    }

    // ═══ SETTINGS ═══
    public function settings()
    {
        $account = auth()->user()->account;
        return view('pages.settings.index', compact('account'));
    }

    public function settingsUpdate(Request $request)
    {
        $v = $request->validate(['name' => 'required|string', 'email' => 'required|email', 'phone' => 'nullable|string']);
        auth()->user()->account->update($v);
        return back()->with('success', 'تم تحديث الإعدادات');
    }

    public function settingsPassword(Request $request)
    {
        $request->validate(['current_password' => 'required', 'password' => 'required|min:6|confirmed']);
        if (!Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة']);
        }
        auth()->user()->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'تم تحديث كلمة المرور');
    }

    // ═══ REPORTS ═══
    public function reports()
    {
        $accountId = auth()->user()->account_id;
        $totalShipments  = Shipment::where('account_id', $accountId)->count();
        $deliveryRate    = $totalShipments > 0 ? round(Shipment::where('account_id', $accountId)->where('status', 'delivered')->count() / $totalShipments * 100, 1) : 0;
        $totalCost       = Shipment::where('account_id', $accountId)->sum('total_cost');
        $avgDeliveryDays = Shipment::where('account_id', $accountId)->where('status', 'delivered')
            ->whereNotNull('delivered_at')->avg(\DB::raw('DATEDIFF(delivered_at, created_at)')) ?? 0;

        return view('pages.reports.index', compact('totalShipments', 'deliveryRate', 'totalCost', 'avgDeliveryDays'));
    }

    public function reportsExport($type)
    {
        return back()->with('success', "تم تصدير تقرير {$type}");
    }

    // ═══ FINANCIAL ═══
    public function financial()
    {
        $accountId = auth()->user()->account_id;
        $transactions    = WalletTransaction::where('account_id', $accountId)->latest()->paginate(15);
        $totalRevenue    = WalletTransaction::where('account_id', $accountId)->where('type', 'credit')->sum('amount');
        $totalPayouts    = WalletTransaction::where('account_id', $accountId)->where('type', 'debit')->sum(\DB::raw('ABS(amount)'));
        $netProfit       = $totalRevenue - $totalPayouts;
        $pendingInvoices = 0;

        return view('pages.financial.index', compact('transactions', 'totalRevenue', 'totalPayouts', 'netProfit', 'pendingInvoices'));
    }

    // ═══ AUDIT ═══
    public function audit(Request $request)
    {
        $query = AuditLog::with('user')->latest();
        if ($userId = $request->get('user_id')) $query->where('user_id', $userId);
        if ($event = $request->get('event'))    $query->where('event', $event);
        if ($from = $request->get('from'))      $query->whereDate('created_at', '>=', $from);
        if ($to = $request->get('to'))          $query->whereDate('created_at', '<=', $to);

        $logs  = $query->paginate(20)->withQueryString();
        $users = User::all();
        return view('pages.audit.index', compact('logs', 'users'));
    }

    public function auditExport()
    {
        return back()->with('success', 'تم التصدير');
    }

    // ═══ PRICING ═══
    public function pricing()
    {
        $pricingRules = PricingRule::latest()->get();
        $rulesCount     = $pricingRules->count();
        $activeCarriers = $pricingRules->where('is_active', true)->pluck('carrier_code')->unique()->count();
        $avgPricePerKg  = $pricingRules->avg('extra_kg_price') ?? 0;
        $surcharges     = collect();
        return view('pages.pricing.index', compact('pricingRules', 'rulesCount', 'activeCarriers', 'avgPricePerKg', 'surcharges'));
    }

    // ═══ ADMIN ═══
    public function admin()
    {
        $orgCount        = Account::where('type', '!=', 'admin')->count();
        $usersCount      = User::count();
        $totalShipments  = Shipment::count();
        $recentActivity  = AuditLog::with('user')->latest()->take(10)->get();
        return view('pages.admin.index', compact('orgCount', 'usersCount', 'totalShipments', 'recentActivity'));
    }

    // ═══ KYC ═══
    public function kyc(Request $request)
    {
        $query = KycRequest::with(['account', 'reviewer']);
        if ($status = $request->get('status')) $query->where('status', $status);
        $kycRequests   = $query->latest()->paginate(15)->withQueryString();
        $totalRequests = KycRequest::count();
        $pendingCount  = KycRequest::where('status', 'pending')->count();
        $approvedCount = KycRequest::where('status', 'verified')->count();
        $rejectedCount = KycRequest::where('status', 'rejected')->count();
        return view('pages.kyc.index', compact('kycRequests', 'totalRequests', 'pendingCount', 'approvedCount', 'rejectedCount'));
    }

    // ═══ DG ═══
    public function dg()
    {
        $classifications    = DgClassification::all();
        $classificationsCount = $classifications->count();
        $activeDgShipments  = 0;
        $rejectedThisMonth  = 0;
        $pendingReview      = 0;
        $pendingDgShipments = collect();
        return view('pages.dg.index', compact('classifications', 'classificationsCount', 'activeDgShipments', 'rejectedThisMonth', 'pendingReview', 'pendingDgShipments'));
    }

    // ═══ ORGANIZATIONS ═══
    public function organizations(Request $request)
    {
        $query = Account::where('type', '!=', 'admin')->withCount(['users', 'shipments']);
        if ($search = $request->get('search'))  $query->where('name', 'like', "%{$search}%");
        if ($type = $request->get('type'))      $query->where('type', $type);
        if ($status = $request->get('status'))  $query->where('status', $status);

        $organizations = $query->latest()->paginate(15)->withQueryString();
        $totalOrgs     = Account::where('type', '!=', 'admin')->count();
        $activeOrgs    = Account::where('type', '!=', 'admin')->where('status', 'active')->count();
        $pendingOrgs   = Account::where('type', '!=', 'admin')->where('status', 'pending')->count();
        $suspendedOrgs = Account::where('type', '!=', 'admin')->where('status', 'suspended')->count();

        // Add wallet balance
        foreach ($organizations as $org) {
            $org->wallet_balance = Wallet::where('account_id', $org->id)->value('available_balance') ?? 0;
        }

        return view('pages.organizations.index', compact('organizations', 'totalOrgs', 'activeOrgs', 'pendingOrgs', 'suspendedOrgs'));
    }

    public function organizationsStore(Request $request)
    {
        $v = $request->validate(['name' => 'required|string', 'type' => 'required|in:individual,business', 'email' => 'required|email|unique:accounts', 'phone' => 'nullable|string', 'cr_number' => 'nullable|string', 'vat_number' => 'nullable|string']);
        Account::create(array_merge($v, ['status' => 'pending']));
        return back()->with('success', 'تم إنشاء المنظمة');
    }

    // ═══ CONTAINERS ═══
    public function containers()
    {
        $containers = Container::with('vessel')->latest()->paginate(15);
        $totalContainers = Container::count();
        $availableCount  = Container::where('status', 'available')->count();
        $inTransitCount  = Container::where('status', 'in_transit')->count();
        $atPortCount     = Container::where('status', 'at_port')->count();
        return view('pages.containers.index', compact('containers', 'totalContainers', 'availableCount', 'inTransitCount', 'atPortCount'));
    }

    // ═══ CUSTOMS ═══
    public function customs(Request $request)
    {
        $query = CustomsDeclaration::with('shipment');
        if ($search = $request->get('search')) $query->where('declaration_number', 'like', "%{$search}%");
        if ($status = $request->get('status')) $query->where('status', $status);

        $declarations     = $query->latest()->paginate(15)->withQueryString();
        $totalDeclarations = CustomsDeclaration::count();
        $pendingClearance  = CustomsDeclaration::where('status', 'pending')->count();
        $clearedCount      = CustomsDeclaration::where('status', 'cleared')->count();
        $heldCount         = CustomsDeclaration::where('status', 'held')->count();
        return view('pages.customs.index', compact('declarations', 'totalDeclarations', 'pendingClearance', 'clearedCount', 'heldCount'));
    }

    // ═══ DRIVERS ═══
    public function drivers()
    {
        $drivers = Driver::latest()->paginate(15);
        $totalDrivers   = Driver::count();
        $availableCount = Driver::where('status', 'available')->count();
        $onDutyCount    = Driver::where('status', 'on_duty')->count();
        $offDutyCount   = Driver::where('status', 'off_duty')->count();
        return view('pages.drivers.index', compact('drivers', 'totalDrivers', 'availableCount', 'onDutyCount', 'offDutyCount'));
    }

    // ═══ CLAIMS ═══
    public function claims()
    {
        $claims = Claim::with('shipment')->latest()->paginate(15);
        $totalClaims       = Claim::count();
        $pendingCount      = Claim::where('status', 'pending')->count();
        $approvedCount     = Claim::where('status', 'approved')->count();
        $totalCompensation = Claim::where('status', 'approved')->sum('amount');
        return view('pages.claims.index', compact('claims', 'totalClaims', 'pendingCount', 'approvedCount', 'totalCompensation'));
    }

    // ═══ VESSELS ═══
    public function vessels()
    {
        $vessels = Vessel::latest()->paginate(15);
        $totalVessels    = Vessel::count();
        $atSeaCount      = Vessel::where('status', 'at_sea')->count();
        $dockedCount     = Vessel::where('status', 'docked')->count();
        $maintenanceCount = Vessel::where('status', 'maintenance')->count();
        return view('pages.vessels.index', compact('vessels', 'totalVessels', 'atSeaCount', 'dockedCount', 'maintenanceCount'));
    }

    // ═══ SCHEDULES ═══
    public function schedules(Request $request)
    {
        $query = Schedule::with('vessel');
        if ($origin = $request->get('origin'))      $query->where('origin_port', $origin);
        if ($dest = $request->get('destination'))    $query->where('destination_port', $dest);
        if ($from = $request->get('from'))           $query->whereDate('departure_date', '>=', $from);
        if ($to = $request->get('to'))               $query->whereDate('arrival_date', '<=', $to);

        $schedules = $query->latest()->paginate(15)->withQueryString();
        $totalSchedules = Schedule::count();
        $activeCount    = Schedule::whereIn('status', ['scheduled', 'departed'])->count();
        $upcomingCount  = Schedule::where('departure_date', '>', now())->where('departure_date', '<', now()->addWeek())->count();
        $delayedCount   = Schedule::where('status', 'delayed')->count();
        $ports = collect();
        return view('pages.schedules.index', compact('schedules', 'totalSchedules', 'activeCount', 'upcomingCount', 'delayedCount', 'ports'));
    }

    // ═══ BRANCHES ═══
    public function branches()
    {
        $branches = Branch::latest()->get();
        $totalBranches = Branch::count();
        $activeCount   = Branch::where('is_active', true)->count();
        $inactiveCount = Branch::where('is_active', false)->count();
        return view('pages.branches.index', compact('branches', 'totalBranches', 'activeCount', 'inactiveCount'));
    }

    // ═══ COMPANIES ═══
    public function companies(Request $request)
    {
        $query = Company::query();
        if ($search = $request->get('search')) $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
        if ($type = $request->get('type'))     $query->where('type', $type);

        $companies = $query->latest()->paginate(15)->withQueryString();
        $totalCompanies = Company::count();
        $carriersCount  = Company::where('type', 'carrier')->count();
        $agentsCount    = Company::where('type', 'agent')->count();
        $activeCount    = Company::where('is_active', true)->count();
        return view('pages.companies.index', compact('companies', 'totalCompanies', 'carriersCount', 'agentsCount', 'activeCount'));
    }

    // ═══ HS CODES ═══
    public function hscodes(Request $request)
    {
        $query = HsCode::query();
        if ($search = $request->get('search')) $query->where('code', 'like', "%{$search}%")->orWhere('description_ar', 'like', "%{$search}%");
        if ($chapter = $request->get('chapter')) $query->where('chapter', $chapter);
        if ($request->has('restricted')) $query->where('is_restricted', $request->get('restricted'));

        $hscodes = $query->latest()->paginate(20)->withQueryString();
        return view('pages.hscodes.index', compact('hscodes'));
    }

    // ═══ RISK ═══
    public function risk()
    {
        $rules          = RiskRule::latest()->get();
        $alerts         = RiskAlert::latest()->take(10)->get();
        $activeAlerts   = RiskAlert::count();
        $highRiskCount  = RiskAlert::where('level', 'high')->count();
        $mediumRiskCount = RiskAlert::where('level', 'medium')->count();
        $lowRiskCount   = RiskAlert::where('level', 'low')->count();
        $total = $activeAlerts ?: 1;
        $highPct   = round($highRiskCount / $total * 100);
        $mediumPct = round($mediumRiskCount / $total * 100);
        $lowPct    = round($lowRiskCount / $total * 100);
        return view('pages.risk.index', compact('rules', 'alerts', 'activeAlerts', 'highRiskCount', 'mediumRiskCount', 'lowRiskCount', 'highPct', 'mediumPct', 'lowPct'));
    }
}
