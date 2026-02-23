<?php

namespace App\Http\Controllers\Web;

use App\Models\Shipment;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\User;
use App\Models\Organization;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class DashboardController extends WebController
{
    public function index()
    {
        $accountId = auth()->user()->account_id;

        // ═══ FIX P0-B7: All counts scoped by account_id ═══
        $shipmentsCount = Shipment::where('account_id', $accountId)->count();
        $ordersCount = Order::where('account_id', $accountId)->count();

        $wallet = Wallet::where('account_id', $accountId)->first();
        $walletBalance = $wallet->available_balance ?? 0;

        // FIX: Notifications scoped by account_id
        $unreadNotifs = Notification::where('account_id', $accountId)
            ->whereNull('read_at')
            ->count();

        // Additional KPIs (scoped)
        $usersCount = User::where('account_id', $accountId)->count();
        $organizationsCount = Organization::where('account_id', $accountId)->count();
        $openTickets = SupportTicket::where('account_id', $accountId)
            ->where('status', 'open')
            ->count();

        // ═══ FIX P0-B5: Remove rand() fallback — show 0 when no data ═══
        $monthlyData = collect(['يناير','فبراير','مارس','أبريل','مايو','يونيو'])->map(fn($name, $i) => [
            'name' => $name,
            'count' => Shipment::where('account_id', $accountId)
                ->whereMonth('created_at', $i + 1)
                ->whereYear('created_at', now()->year)
                ->count(),
            // REMOVED: ?: rand(50, 350) — now returns actual 0
        ])->toArray();
        $maxMonthly = max(array_column($monthlyData, 'count') ?: [1]);

        // ═══ FIX P0-B5: Replace hardcoded carrier stats with real DB aggregation ═══
        $carrierRaw = Shipment::where('account_id', $accountId)
            ->select('carrier_code', DB::raw('count(*) as total'))
            ->groupBy('carrier_code')
            ->orderByDesc('total')
            ->get();

        $totalShipments = $carrierRaw->sum('total');
        $colors = ['var(--pr)', 'var(--ac)', 'var(--wn)', 'var(--in)', 'var(--dg)', 'var(--pp)'];

        if ($totalShipments > 0) {
            $carrierStats = $carrierRaw->map(fn($c, $i) => [
                'name' => $c->carrier_code ?? 'غير محدد',
                'percent' => round(($c->total / $totalShipments) * 100),
                'color' => $colors[$i % count($colors)],
            ]);
        } else {
            $carrierStats = collect([
                ['name' => 'لا توجد بيانات', 'percent' => 100, 'color' => 'var(--border)'],
            ]);
        }

        // Status distribution (scoped)
        $statusDistribution = Shipment::where('account_id', $accountId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $recentShipments = Shipment::where('account_id', $accountId)
            ->latest()
            ->take(7)
            ->get();

        return view('pages.dashboard.index', compact(
            'shipmentsCount', 'ordersCount', 'walletBalance', 'unreadNotifs',
            'usersCount', 'organizationsCount', 'openTickets',
            'monthlyData', 'maxMonthly', 'carrierStats', 'recentShipments',
            'statusDistribution'
        ));
    }
}
