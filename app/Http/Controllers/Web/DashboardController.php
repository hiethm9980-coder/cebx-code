<?php

namespace App\Http\Controllers\Web;

use App\Models\{Shipment, Order, Store, Wallet, WalletTransaction, SupportTicket, Account, User};
use Illuminate\Support\Facades\DB;

class DashboardController extends WebController
{
    public function index()
    {
        $portalType = $this->portalType();

        return match ($portalType) {
            'admin' => $this->adminDashboard(),
            'b2c'   => $this->b2cDashboard(),
            default => $this->b2bDashboard(),
        };
    }

    // ════════════════════════════════════════════════
    //  B2C DASHBOARD — Personal shipping
    // ════════════════════════════════════════════════
    private function b2cDashboard()
    {
        $user      = auth()->user();
        $accountId = $user->account_id;
        $wallet    = Wallet::where('account_id', $accountId)->first();

        $totalShipments   = Shipment::where('account_id', $accountId)->count();
        $activeShipments  = Shipment::where('account_id', $accountId)->whereNotIn('status', ['delivered', 'cancelled'])->count();
        $deliveredCount   = Shipment::where('account_id', $accountId)->where('status', 'delivered')->count();
        $recentShipments  = Shipment::where('account_id', $accountId)->latest()->take(5)->get();

        return view('pages.dashboard.index', [
            'todayShipments'     => $activeShipments,
            'shipmentsTrend'     => 0,
            'walletBalance'      => $wallet->available_balance ?? 0,
            'recentShipments'    => $recentShipments,
            'totalShipments'     => $totalShipments,
            'deliveredCount'     => $deliveredCount,
            'newOrders'          => 0,
            'storesCount'        => 0,
            'exceptions'         => 0,
            'monthlyData'        => $this->monthlyChart($accountId),
            'maxMonthly'         => 1,
            'statusDistribution' => $this->statusDistribution($accountId),
            'carrierStats'       => collect(),
        ]);
    }

    // ════════════════════════════════════════════════
    //  B2B DASHBOARD — Business operations
    // ════════════════════════════════════════════════
    private function b2bDashboard()
    {
        $user      = auth()->user();
        $accountId = $user->account_id;
        $wallet    = Wallet::where('account_id', $accountId)->first();

        $todayShipments = Shipment::where('account_id', $accountId)
            ->whereDate('created_at', today())->count();

        $yesterdayCount = Shipment::where('account_id', $accountId)
            ->whereDate('created_at', today()->subDay())->count();

        $trend = $yesterdayCount > 0
            ? round(($todayShipments - $yesterdayCount) / $yesterdayCount * 100) : 0;

        $total = Shipment::where('account_id', $accountId)->count() ?: 1;
        $recentShipments = Shipment::where('account_id', $accountId)->latest()->take(5)->get();

        return view('pages.dashboard.index', [
            'todayShipments'     => $todayShipments,
            'shipmentsTrend'     => $trend,
            'walletBalance'      => $wallet->available_balance ?? 0,
            'newOrders'          => Order::where('account_id', $accountId)->where('status', 'new')->count(),
            'storesCount'        => Store::where('account_id', $accountId)->count(),
            'exceptions'         => Shipment::where('account_id', $accountId)->where('status', 'exception')->count(),
            'recentShipments'    => $recentShipments,
            'monthlyData'        => $this->monthlyChart($accountId),
            'maxMonthly'         => 1,
            'statusDistribution' => $this->statusDistribution($accountId),
            'carrierStats'       => $this->carrierStats($accountId),
        ]);
    }

    // ════════════════════════════════════════════════
    //  ADMIN DASHBOARD — System-wide overview
    // ════════════════════════════════════════════════
    private function adminDashboard()
    {
        // System-wide stats — NO account_id filter
        $totalShipments = Shipment::count();
        $todayShipments = Shipment::whereDate('created_at', today())->count();
        $yesterdayCount  = Shipment::whereDate('created_at', today()->subDay())->count();
        $trend = $yesterdayCount > 0 ? round(($todayShipments - $yesterdayCount) / $yesterdayCount * 100) : 0;

        $totalAccounts  = Account::count();
        $totalUsers     = User::count();
        $totalRevenue   = WalletTransaction::where('type', 'debit')->sum(DB::raw('ABS(amount)'));
        $openTickets    = SupportTicket::where('status', 'open')->count();
        $totalOrders    = Order::count();
        $totalStores    = Store::count();

        // Recent shipments — ALL accounts
        $recentShipments = Shipment::with('account')->latest()->take(10)->get();

        return view('pages.dashboard.index', [
            // Admin-specific
            'totalAccounts'      => $totalAccounts,
            'totalUsers'         => $totalUsers,
            'totalRevenue'       => $totalRevenue,
            'openTickets'        => $openTickets,
            'totalOrders'        => $totalOrders,
            'totalStores'        => $totalStores,
            // Shared vars (needed by view)
            'todayShipments'     => $todayShipments,
            'shipmentsTrend'     => $trend,
            'walletBalance'      => $totalRevenue,
            'newOrders'          => Order::where('status', 'new')->count(),
            'storesCount'        => $totalStores,
            'exceptions'         => Shipment::where('status', 'exception')->count(),
            'recentShipments'    => $recentShipments,
            'monthlyData'        => $this->monthlyChart(null),
            'maxMonthly'         => 1,
            'statusDistribution' => $this->statusDistribution(null),
            'carrierStats'       => $this->carrierStats(null),
        ]);
    }

    // ── Helpers ──

    private function monthlyChart(?int $accountId): array
    {
        $data = collect(range(5, 0))->map(function ($i) use ($accountId) {
            $date = now()->subMonths($i);
            $q = Shipment::query();
            if ($accountId) $q->where('account_id', $accountId);
            return [
                'name'  => $date->translatedFormat('M'),
                'count' => $q->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
            ];
        })->toArray();

        // Fix maxMonthly
        $max = max(array_column($data, 'count'));
        return $data;
    }

    private function statusDistribution(?int $accountId): array
    {
        $q = Shipment::query();
        if ($accountId) $q->where('account_id', $accountId);
        $total = (clone $q)->count() ?: 1;

        return collect([
            ['label' => 'تم التسليم',   'status' => 'delivered',  'color' => '#10B981'],
            ['label' => 'في الطريق',    'status' => 'in_transit', 'color' => '#3B82F6'],
            ['label' => 'قيد المعالجة',  'status' => 'processing', 'color' => '#F59E0B'],
            ['label' => 'ملغي',          'status' => 'cancelled',  'color' => '#EF4444'],
        ])->map(function ($item) use ($accountId, $total) {
            $q = Shipment::where('status', $item['status']);
            if ($accountId) $q->where('account_id', $accountId);
            $item['pct'] = round($q->count() / $total * 100);
            return $item;
        })->toArray();
    }

    private function carrierStats(?int $accountId)
    {
        $q = Shipment::query()->whereNotNull('carrier_name');
        if ($accountId) $q->where('account_id', $accountId);
        $total = (clone $q)->count() ?: 1;

        return $q->select('carrier_name', DB::raw('count(*) as total'))
            ->groupBy('carrier_name')->orderByDesc('total')->take(5)->get()
            ->map(fn($c) => ['name' => $c->carrier_name, 'percent' => round($c->total / $total * 100)]);
    }
}
