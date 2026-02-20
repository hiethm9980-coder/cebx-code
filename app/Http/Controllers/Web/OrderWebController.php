<?php

namespace App\Http\Controllers\Web;

use App\Models\{Order, Shipment, ShipmentEvent};
use Illuminate\Http\Request;

class OrderWebController extends WebController
{
    public function index(Request $request)
    {
        $query = Order::with('store');
        if (!$this->isAdmin()) {
            $query->where('account_id', auth()->user()->account_id);
        }

        if ($status = $request->get('status')) $query->where('status', $status);
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn($q) => $q
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%"));
        }

        $orders   = $query->latest()->paginate(20)->withQueryString();
        $statsQ   = fn() => $this->isAdmin() ? Order::query() : Order::where('account_id', auth()->user()->account_id);
        $newCount = $statsQ()->where('status', 'new')->count();
        $shippedCount = $statsQ()->where('status', 'shipped')->count();

        return view('pages.orders.index', compact('orders', 'newCount', 'shippedCount'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'order_number' => 'required|string', 'customer_name' => 'required|string',
            'customer_phone' => 'required|string', 'store_id' => 'nullable|integer', 'total_amount' => 'nullable|numeric',
        ]);
        $order = Order::create(array_merge($v, [
            'account_id' => auth()->user()->account_id,
            'status' => 'new',
        ]));
        return redirect()->route('orders.index')->with('success', 'تم إنشاء الطلب');
    }

    public function ship(Order $order)
    {
        $shipment = Shipment::create([
            'account_id'       => $order->account_id,
            'user_id'          => auth()->id(),
            'order_id'         => $order->id,
            'reference_number' => Shipment::generateRef(),
            'type'             => 'domestic',
            'status'           => 'pending',
            'recipient_name'   => $order->customer_name,
            'recipient_phone'  => $order->customer_phone,
            'source'           => 'store_sync',
        ]);
        $order->update(['status' => 'shipped', 'shipment_id' => $shipment->id]);
        ShipmentEvent::create([
            'shipment_id' => $shipment->id, 'status' => 'pending',
            'description' => "شحنة من طلب #{$order->order_number}", 'event_at' => now(),
        ]);
        return back()->with('success', "تم شحن الطلب #{$order->order_number}");
    }

    public function cancel(Order $order)
    {
        $order->update(['status' => 'cancelled']);
        return back()->with('warning', "تم إلغاء الطلب #{$order->order_number}");
    }
}
