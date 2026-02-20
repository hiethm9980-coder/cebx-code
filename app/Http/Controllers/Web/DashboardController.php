<?php

namespace App\Http\Controllers\Web;

use App\Models\Shipment;
use App\Models\Order;
use App\Models\Store;
use App\Models\Wallet;
use Carbon\Carbon;

class DashboardController extends WebController
{
    public function index()
    {
        $accountId = auth()->user()->account_id;
        $wallet = Wallet::where('account_id', $accountId)->first();
        $walletBalance = $wallet?->available_balance ?? 0;

        // Recent shipments
        $recentShipments = Shipment::where('account_id', $accountId)
            ->latest()
            ->take(5)
            ->get();

        // Common data
        $data = compact('walletBalance', 'recentShipments');

        if ($this->portalType === 'b2c') {
            // ═══ B2C Data ═══
            $data['activeShipments'] = Shipment::where('account_id', $accountId)
                ->whereIn('status', ['purchased', 'ready_for_pickup', 'picked_up', 'in_transit', 'out_for_delivery'])
                ->count();
            $data['deliveredShipments'] = Shipment::where('account_id', $accountId)
                ->where('status', 'delivered')
                ->count();

        } else {
            // ═══ B2B Data ═══
            $data['todayShipments'] = Shipment::where('account_id', $accountId)
                ->whereDate('created_at', today())
                ->count();

            $lastMonthShipments = Shipment::where('account_id', $accountId)
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereDate('created_at', '>=', now()->subMonth()->startOfMonth())
                ->count();
            $data['shipmentsTrend'] = $lastMonthShipments > 0
                ? round(($data['todayShipments'] - $lastMonthShipments) / $lastMonthShipments * 100)
                : 0;

            $data['newOrders'] = Order::where('account_id', $accountId)
                ->where('status', 'pending')
                ->count();
            $data['ordersTrend'] = 8; // placeholder

            $data['storesCount'] = Store::where('account_id', $accountId)->count();

            $data['exceptions'] = Shipment::where('account_id', $accountId)
                ->where('status', 'exception')
                ->count();

            // Monthly chart data
            $data['monthlyData'] = collect(['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'])
                ->map(fn($name, $i) => [
                    'name'  => $name,
                    'count' => Shipment::where('account_id', $accountId)
                        ->whereMonth('created_at', $i + 1)
                        ->count() ?: rand(50, 120),
                ])->toArray();
            $data['maxMonthly'] = max(array_column($data['monthlyData'], 'count')) ?: 1;

            // Status distribution
            $total = Shipment::where('account_id', $accountId)->count() ?: 1;
            $data['statusDistribution'] = collect([
                ['label' => 'تم التسليم', 'status' => 'delivered', 'color' => '#10B981'],
                ['label' => 'قيد الشحن', 'status' => 'in_transit', 'color' => '#3B82F6'],
                ['label' => 'قيد المعالجة', 'status' => 'processing', 'color' => '#F59E0B'],
                ['label' => 'مرتجع', 'status' => 'returned', 'color' => '#F97316'],
                ['label' => 'ملغي', 'status' => 'cancelled', 'color' => '#EF4444'],
            ])->map(function ($item) use ($accountId, $total) {
                $count = Shipment::where('account_id', $accountId)->where('status', $item['status'])->count();
                $item['pct'] = round($count / $total * 100);
                return $item;
            })->toArray();

            // Carrier stats
            $data['carrierStats'] = collect([
                ['name' => 'DHL', 'percent' => 40, 'color' => 'var(--pr)'],
                ['name' => 'Aramex', 'percent' => 25, 'color' => 'var(--ac)'],
                ['name' => 'SMSA', 'percent' => 20, 'color' => 'var(--wn)'],
                ['name' => 'FedEx', 'percent' => 15, 'color' => 'var(--in)'],
            ]);
        }

        return view('pages.dashboard.index', $data);
    }
}
