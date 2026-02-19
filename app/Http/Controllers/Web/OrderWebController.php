<?php
namespace App\Http\Controllers\Web;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;

class OrderWebController extends WebController
{
    public function index() {
        return view('pages.orders.index', [
            'orders' => Order::with('store')->latest()->paginate(20),
        ]);
    }
    public function store(Request $r) {
        $data = $r->validate([
            'customer_name' => 'required|string|max:200',
            'total_amount' => 'required|numeric',
            'customer_email' => 'nullable|email',
            'shipping_address' => 'nullable|string|max:300',
        ]);
        $accountId = auth()->user()->account_id;
        $store = Store::where('account_id', $accountId)->first();
        if (!$store) {
            return back()->with('error', 'يجب إنشاء متجر أولاً.');
        }
        $orderNumber = 'ORD-' . strtoupper(uniqid());
        Order::create([
            'account_id' => $accountId,
            'store_id' => $store->id,
            'external_order_id' => $orderNumber,
            'external_order_number' => $orderNumber,
            'source' => Order::SOURCE_MANUAL,
            'status' => Order::STATUS_PENDING,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'] ?? null,
            'total_amount' => (float) $data['total_amount'],
            'currency' => 'SAR',
            'shipping_address_line_1' => $data['shipping_address'] ?? null,
        ]);
        return back()->with('success', 'تم إنشاء الطلب');
    }
    public function ship(Order $order) {
        $order->update(['status' => Order::STATUS_SHIPPED]);
        return back()->with('success', 'تم شحن الطلب');
    }
    public function cancel(Order $order) {
        $order->update(['status' => 'cancelled']);
        return back()->with('warning', 'تم إلغاء الطلب');
    }
}
