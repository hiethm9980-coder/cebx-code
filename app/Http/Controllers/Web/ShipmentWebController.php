<?php

namespace App\Http\Controllers\Web;

use App\Models\{Shipment, ShipmentEvent, Address, Wallet, WalletTransaction};
use Illuminate\Http\Request;

class ShipmentWebController extends WebController
{
    public function index(Request $request)
    {
        $query = Shipment::query();

        // Admin: see ALL — B2B/B2C: see only own account
        if (!$this->isAdmin()) {
            $query->where('account_id', auth()->user()->account_id);
        } else {
            $query->with('account');
        }

        if ($status = $request->get('status'))  $query->where('status', $status);
        if ($carrier = $request->get('carrier')) $query->where('carrier_code', $carrier);
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(fn($q) => $q
                ->where('reference_number', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhere('carrier_tracking_number', 'like', "%{$search}%"));
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();

        // Stats scoped the same way
        $statsQuery = fn() => $this->isAdmin() ? Shipment::query() : Shipment::where('account_id', auth()->user()->account_id);

        $allCount       = $statsQuery()->count();
        $inTransitCount = $statsQuery()->where('status', 'in_transit')->count();
        $deliveredCount = $statsQuery()->where('status', 'delivered')->count();
        $pendingCount   = $statsQuery()->where('status', 'pending')->count();

        return view('pages.shipments.index', compact(
            'shipments', 'allCount', 'inTransitCount', 'deliveredCount', 'pendingCount'
        ));
    }

    public function create()
    {
        $savedAddresses = Address::where('account_id', auth()->user()->account_id)->get();
        return view('pages.shipments.create', compact('savedAddresses'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'sender_name' => 'required|string|max:200',
            'sender_phone' => 'required|string|max:30',
            'sender_city' => 'required|string|max:100',
            'recipient_name' => 'required|string|max:200',
            'recipient_phone' => 'required|string|max:30',
            'recipient_city' => 'required|string|max:100',
            'weight' => 'nullable|numeric|min:0',
            'pieces' => 'nullable|integer|min:1',
            'content_description' => 'nullable|string|max:500',
        ]);

        $accountId = auth()->user()->account_id;
        $cost = max(($v['weight'] ?? 1) * 6.5, 18);
        $vat  = round($cost * 0.15, 2);

        $shipment = Shipment::create([
            'account_id'          => $accountId,
            'user_id'             => auth()->id(),
            'reference_number'    => Shipment::generateRef(),
            'type'                => 'domestic',
            'sender_name'         => $v['sender_name'],
            'sender_phone'        => $v['sender_phone'],
            'sender_city'         => $v['sender_city'],
            'recipient_name'      => $v['recipient_name'],
            'recipient_phone'     => $v['recipient_phone'],
            'recipient_city'      => $v['recipient_city'],
            'weight'              => $v['weight'] ?? 1,
            'pieces'              => $v['pieces'] ?? 1,
            'content_description' => $v['content_description'] ?? null,
            'shipping_cost'       => $cost,
            'vat_amount'          => $vat,
            'total_cost'          => $cost + $vat,
            'status'              => 'pending',
            'source'              => 'manual',
        ]);

        // Deduct from wallet
        $wallet = Wallet::firstOrCreate(['account_id' => $accountId], ['available_balance' => 0]);
        if ($wallet->available_balance >= $shipment->total_cost) {
            $wallet->decrement('available_balance', $shipment->total_cost);
            WalletTransaction::create([
                'wallet_id'        => $wallet->id,
                'account_id'       => $accountId,
                'reference_number' => 'TXN-' . str_pad(WalletTransaction::count() + 1, 5, '0', STR_PAD_LEFT),
                'type'             => 'debit',
                'description'      => "شحنة {$shipment->reference_number} — {$shipment->carrier_name}",
                'amount'           => -$shipment->total_cost,
                'balance_after'    => $wallet->available_balance,
                'status'           => 'completed',
            ]);
        }

        ShipmentEvent::create([
            'shipment_id' => $shipment->id,
            'status'      => 'pending',
            'description' => 'تم إنشاء الشحنة',
            'event_at'    => now(),
        ]);

        return redirect()->route('shipments.show', $shipment)->with('success', 'تم إنشاء الشحنة بنجاح');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load('events', 'account');
        $trackingHistory = $shipment->events->map(fn($e) => [
            'title'    => $e->description,
            'date'     => $e->event_at->format('d/m/Y — h:i A'),
            'location' => $e->location,
        ])->toArray();

        if (empty($trackingHistory)) {
            $trackingHistory = [
                ['title' => 'تم إنشاء الشحنة', 'date' => $shipment->created_at->format('d/m/Y — h:i A')],
            ];
        }

        return view('pages.shipments.show', compact('shipment', 'trackingHistory'));
    }

    public function cancel(Shipment $shipment)
    {
        if (in_array($shipment->status, ['delivered', 'cancelled'])) {
            return back()->with('error', 'لا يمكن إلغاء هذه الشحنة');
        }

        $shipment->update(['status' => 'cancelled']);
        ShipmentEvent::create(['shipment_id' => $shipment->id, 'status' => 'cancelled', 'description' => 'تم إلغاء الشحنة', 'event_at' => now()]);

        $wallet = Wallet::where('account_id', $shipment->account_id)->first();
        if ($wallet && $shipment->total_cost > 0) {
            $wallet->increment('available_balance', $shipment->total_cost);
            WalletTransaction::create([
                'wallet_id' => $wallet->id, 'account_id' => $shipment->account_id,
                'reference_number' => 'TXN-' . str_pad(WalletTransaction::count() + 1, 5, '0', STR_PAD_LEFT),
                'type' => 'refund', 'description' => "استرداد شحنة ملغاة {$shipment->reference_number}",
                'amount' => $shipment->total_cost, 'balance_after' => $wallet->available_balance, 'status' => 'completed',
            ]);
        }

        return back()->with('warning', 'تم إلغاء الشحنة واسترداد المبلغ');
    }

    public function createReturn(Shipment $shipment)
    {
        $ret = $shipment->replicate();
        $ret->fill([
            'reference_number' => 'RET-' . Shipment::count(),
            'type' => 'return', 'status' => 'pending',
            'sender_name' => $shipment->recipient_name, 'sender_phone' => $shipment->recipient_phone,
            'sender_city' => $shipment->recipient_city,
            'recipient_name' => $shipment->sender_name, 'recipient_phone' => $shipment->sender_phone,
            'recipient_city' => $shipment->sender_city,
        ]);
        $ret->save();
        return redirect()->route('shipments.show', $ret)->with('success', 'تم إنشاء شحنة الإرجاع');
    }

    public function label(Shipment $shipment)
    {
        return $shipment->label_url ? redirect($shipment->label_url) : back()->with('error', 'لا يوجد ملصق');
    }

    public function export()
    {
        $query = Shipment::query();
        if (!$this->isAdmin()) $query->where('account_id', auth()->user()->account_id);
        $shipments = $query->latest()->get();

        $csv = "\xEF\xBB\xBF" . "رقم التتبع,المستلم,الناقل,المدينة,الحالة,التاريخ\n";
        foreach ($shipments as $s) {
            $csv .= "{$s->reference_number},{$s->recipient_name},{$s->carrier_name},{$s->recipient_city},{$s->status},{$s->created_at->format('Y-m-d')}\n";
        }
        return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="shipments.csv"']);
    }
}
