<?php

namespace App\Http\Controllers\Web;

use App\Models\{Order, Shipment, ShipmentEvent};
use Illuminate\Http\Request;

class OrderWebController extends WebController
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $query = Order::where('account_id', $accountId)->with('store');

        if ($status = $request->get('status')) $query->where('status', $status);
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn($q) => $q->where('order_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"));
        }

        $orders = $query->latest()->paginate(20)->withQueryString();
        return view('pages.orders.index', compact('orders'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'customer_name' => 'required|string|max:200',
            'customer_phone' => 'required|string',
            'customer_city' => 'required|string',
            'items_count' => 'nullable|integer|min:1',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        Order::create(array_merge($v, [
            'account_id'   => auth()->user()->account_id,
            'order_number' => '#ORD-' . str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT),
            'status'       => 'new',
        ]));

        return back()->with('success', 'تم إنشاء الطلب');
    }

    public function ship(Order $order)
    {
        $shipment = Shipment::create([
            'account_id'       => $order->account_id,
            'user_id'          => auth()->id(),
            'reference_number' => Shipment::generateRef(),
            'type'             => 'domestic',
            'sender_name'      => auth()->user()->account->name ?? 'المرسل',
            'sender_phone'     => auth()->user()->phone ?? '',
            'sender_city'      => 'الرياض',
            'recipient_name'   => $order->customer_name,
            'recipient_phone'  => $order->customer_phone ?? '',
            'recipient_city'   => $order->customer_city ?? 'الرياض',
            'status'           => 'pending',
            'source'           => 'store_sync',
            'order_id'         => $order->id,
        ]);

        $order->update(['status' => 'shipped', 'shipment_id' => $shipment->id]);

        ShipmentEvent::create(['shipment_id' => $shipment->id, 'status' => 'pending', 'description' => "شحنة من طلب {$order->order_number}", 'event_at' => now()]);

        return back()->with('success', "تم شحن الطلب {$order->order_number}");
    }

    public function cancel(Order $order)
    {
        $order->update(['status' => 'cancelled']);
        return back()->with('warning', 'تم إلغاء الطلب');
    }
}
