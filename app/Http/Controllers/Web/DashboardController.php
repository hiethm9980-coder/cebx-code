<?php

namespace App\Http\Controllers\Web;

use App\Models\{Shipment, Order, Store, Wallet, SupportTicket};
use Illuminate\Support\Facades\DB;

class DashboardController extends WebController
{
    public function index()
    {
        $user      = auth()->user();
        $accountId = $user->account_id;
        $wallet    = Wallet::where('account_id', $accountId)->first();

        $recentShipments = Shipment::where('account_id', $accountId)->latest()->take(5)->get();

        $data = [
            'walletBalance'   => $wallet->available_balance ?? 0,
            'recentShipments' => $recentShipments,
        ];

        // ─── B2B DATA ───
        $data['todayShipments'] = Shipment::where('account_id', $accountId)
            ->whereDate('created_at', today())->count();

        $yesterdayCount = Shipment::where('account_id', $accountId)
            ->whereDate('created_at', today()->subDay())->count();
        $data['shipmentsTrend'] = $yesterdayCount > 0
            ? round(($data['todayShipments'] - $yesterdayCount) / $yesterdayCount * 100) : 0;

        $data['newOrders']   = Order::where('account_id', $accountId)->where('status', 'new')->count();
        $data['storesCount'] = Store::where('account_id', $accountId)->count();
        $data['exceptions']  = Shipment::where('account_id', $accountId)->where('status', 'exception')->count();

        // Monthly chart — last 6 months
        $data['monthlyData'] = collect(range(5, 0))->map(function ($i) use ($accountId) {
            $date = now()->subMonths($i);
            return [
                'name'  => $date->translatedFormat('M'),
                'count' => Shipment::where('account_id', $accountId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
            ];
        })->toArray();
        $data['maxMonthly'] = max(array_column($data['monthlyData'], 'count')) ?: 1;

        // Status distribution
        $total = Shipment::where('account_id', $accountId)->count() ?: 1;
        $data['statusDistribution'] = collect([
            ['label' => 'تم التسليم', 'status' => 'delivered',   'color' => '#10B981'],
            ['label' => 'في الطريق',  'status' => 'in_transit',  'color' => '#3B82F6'],
            ['label' => 'قيد المعالجة','status' => 'processing',  'color' => '#F59E0B'],
            ['label' => 'ملغي',        'status' => 'cancelled',   'color' => '#EF4444'],
        ])->map(function ($item) use ($accountId, $total) {
            $count = Shipment::where('account_id', $accountId)->where('status', $item['status'])->count();
            $item['pct'] = round($count / $total * 100);
            return $item;
        })->toArray();

        // Carrier stats
        $data['carrierStats'] = Shipment::where('account_id', $accountId)
            ->select('carrier_name', DB::raw('count(*) as total'))
            ->whereNotNull('carrier_name')
            ->groupBy('carrier_name')->orderByDesc('total')->take(5)->get()
            ->map(fn($c) => ['name' => $c->carrier_name, 'percent' => round($c->total / $total * 100)]);

        return view('pages.dashboard.index', $data);
    }
}
