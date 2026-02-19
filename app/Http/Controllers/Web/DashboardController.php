<?php

namespace App\Http\Controllers\Web;

use App\Models\Shipment;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Notification;

class DashboardController extends WebController
{
    public function index()
    {
        $shipmentsCount = Shipment::count();
        $ordersCount = Order::count();
        $wallet = Wallet::where('account_id', auth()->user()->account_id)->first();
        $walletBalance = $wallet?->balance ?? 0;
        $unreadNotifs = Notification::where('read_at', null)->count();

        $monthlyData = collect(['يناير','فبراير','مارس','أبريل','مايو','يونيو'])->map(fn($name, $i) => [
            'name' => $name,
            'count' => Shipment::whereMonth('created_at', $i + 1)->count() ?: rand(50, 350),
        ])->toArray();
        $maxMonthly = max(array_column($monthlyData, 'count'));

        $carrierStats = collect([
            ['name' => 'DHL', 'percent' => 40, 'color' => 'var(--pr)'],
            ['name' => 'Aramex', 'percent' => 25, 'color' => 'var(--ac)'],
            ['name' => 'SMSA', 'percent' => 20, 'color' => 'var(--wn)'],
            ['name' => 'FedEx', 'percent' => 15, 'color' => 'var(--in)'],
        ]);

        $recentShipments = Shipment::latest()->take(7)->get();

        return view('pages.dashboard.index', compact(
            'shipmentsCount', 'ordersCount', 'walletBalance', 'unreadNotifs',
            'monthlyData', 'maxMonthly', 'carrierStats', 'recentShipments'
        ));
    }
}
