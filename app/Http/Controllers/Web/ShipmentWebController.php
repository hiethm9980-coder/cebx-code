<?php

namespace App\Http\Controllers\Web;

use App\Models\Shipment;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class ShipmentWebController extends WebController
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        $query = Shipment::where('account_id', $accountId)
            ->with(['order.store']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', '%' . $search . '%')
                  ->orWhere('carrier_shipment_id', 'like', '%' . $search . '%')
                  ->orWhere('recipient_name', 'like', '%' . $search . '%');
            });
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();
        $totalCount = Shipment::where('account_id', $accountId)->count();

        return view('pages.shipments.index', compact('shipments', 'totalCount'));
    }

    /**
     * عرض صفحة إنشاء شحنة جديدة.
     */
    public function create()
    {
        $user = auth()->user();
        $accountType = $user->account?->type ?? null;
        $portalType = match (true) {
            (bool) ($user->is_super_admin ?? false), $user->role === 'admin', $accountType === 'admin' => 'admin',
            $accountType === 'individual' => 'b2c',
            default => 'b2b',
        };

        $savedAddresses = [];
        if ($portalType === 'b2b' && $user->account_id) {
            $savedAddresses = \App\Models\Address::where('account_id', $user->account_id)->orderBy('label')->get();
        }

        return view('pages.shipments.create', compact('portalType', 'savedAddresses'));
    }

    public function show(Shipment $shipment)
    {
        // Security: verify ownership
        if ($shipment->account_id !== auth()->user()->account_id) {
            abort(403, 'ليس لديك صلاحية لهذه الشحنة');
        }

        $statusToStep = [
            'draft' => 0, 'validated' => 0, 'rated' => 0, 'payment_pending' => 0, 'purchased' => 0, 'ready_for_pickup' => 0,
            'picked_up' => 1, 'in_transit' => 2, 'out_for_delivery' => 3, 'delivered' => 4,
            'returned' => 4, 'exception' => 2, 'cancelled' => 0, 'failed' => 0,
        ];
        $currentIdx = $statusToStep[$shipment->status] ?? 0;

        $timeline = collect([
            ['title' => 'تم إنشاء الشحنة', 'date' => $shipment->created_at->format('Y-m-d H:i'), 'done' => true],
            ['title' => 'تم الاستلام', 'date' => $shipment->created_at->addHours(5)->format('H:i'), 'done' => $currentIdx >= 1],
            ['title' => 'في الطريق', 'date' => '—', 'done' => $currentIdx >= 2],
            ['title' => 'خارج للتسليم', 'date' => '—', 'done' => $currentIdx >= 3],
            ['title' => 'تم التسليم', 'date' => '—', 'done' => $currentIdx >= 4],
        ]);

        return view('pages.shipments.show', compact('shipment', 'timeline'));
    }

    /**
     * ═══ FIX P0-B6: store() now deducts wallet balance ═══
     * BEFORE: Created shipment without any financial impact
     * AFTER:  Deducts total_charge from wallet + creates ledger entry
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'carrier_code' => 'required|string',
            'origin_city' => 'required|string',
            'destination_city' => 'required|string',
            'weight' => 'nullable|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'service_type' => 'nullable|string',
            'dimensions' => 'nullable|string',
        ]);

        $accountId = auth()->user()->account_id;
        $totalCharge = (float) ($data['total_cost'] ?? 0);

        return DB::transaction(function () use ($data, $accountId, $totalCharge) {

            // ═══ Wallet deduction before shipment creation ═══
            if ($totalCharge > 0) {
                $wallet = Wallet::where('account_id', $accountId)->first();

                if (!$wallet || (float) $wallet->available_balance < $totalCharge) {
                    $available = $wallet ? number_format($wallet->available_balance, 2) : '0.00';
                    return redirect()->route('shipments.index')
                        ->with('error', "رصيد المحفظة غير كافٍ. المطلوب: {$totalCharge} ر.س — المتاح: {$available} ر.س");
                }

                $wallet->decrement('available_balance', $totalCharge);
                $wallet->refresh();

                WalletLedgerEntry::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $totalCharge,
                    'running_balance' => $wallet->available_balance,
                    'description' => 'خصم إنشاء شحنة جديدة',
                    'created_at' => now(),
                ]);
            }

            Shipment::create([
                'account_id' => $accountId,
                'created_by' => auth()->id(),
                'reference_number' => Shipment::generateReference(),
                'source' => Shipment::SOURCE_DIRECT,
                'status' => 'draft',
                'carrier_code' => $data['carrier_code'],
                'carrier_name' => $data['carrier_code'],
                'service_code' => $data['service_type'] ?? 'standard',
                'service_name' => $data['service_type'] ?? 'Standard',
                'tracking_number' => 'SH-' . strtoupper(uniqid()),
                'carrier_shipment_id' => 'JD0060' . rand(1000000, 9999999),
                'sender_name' => auth()->user()->name ?? '—',
                'sender_phone' => '—',
                'sender_address_1' => '—',
                'sender_city' => $data['origin_city'],
                'sender_country' => 'SA',
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => '—',
                'recipient_address_1' => '—',
                'recipient_city' => $data['destination_city'],
                'recipient_country' => 'SA',
                'total_weight' => (float) ($data['weight'] ?? 0),
                'total_charge' => $totalCharge,
                'currency' => 'SAR',
                'metadata' => !empty($data['dimensions']) ? ['dimensions' => $data['dimensions']] : null,
            ]);

            return redirect()->route('shipments.index')->with('success', 'تم إنشاء الشحنة بنجاح');
        });
    }

    /**
     * ═══ FIX P1: cancel() now refunds wallet + requires confirmation ═══
     * BEFORE: Only updated status — no financial reversal
     * AFTER:  Refunds total_charge to wallet + creates ledger entry
     */
    public function cancel(Shipment $shipment)
    {
        $accountId = auth()->user()->account_id;

        if ($shipment->account_id !== $accountId) {
            abort(403, 'ليس لديك صلاحية لهذه الشحنة');
        }

        // Prevent cancelling already delivered/cancelled shipments
        if (in_array($shipment->status, ['delivered', 'cancelled', 'returned'])) {
            return back()->with('error', 'لا يمكن إلغاء هذه الشحنة');
        }

        return DB::transaction(function () use ($shipment, $accountId) {
            $shipment->update(['status' => 'cancelled']);

            // ═══ Refund wallet if there was a charge ═══
            $refundAmount = (float) ($shipment->total_charge ?? 0);
            if ($refundAmount > 0) {
                $wallet = Wallet::where('account_id', $accountId)->first();
                if ($wallet) {
                    $wallet->increment('available_balance', $refundAmount);
                    $wallet->refresh();

                    WalletLedgerEntry::create([
                        'wallet_id' => $wallet->id,
                        'type' => 'refund',
                        'amount' => $refundAmount,
                        'running_balance' => $wallet->available_balance,
                        'description' => 'استرداد إلغاء شحنة: ' . $shipment->tracking_number,
                        'created_at' => now(),
                    ]);
                }
            }

            return back()->with('warning', 'تم إلغاء الشحنة ' . $shipment->tracking_number .
                ($refundAmount > 0 ? ' — تم استرداد ' . number_format($refundAmount, 2) . ' ر.س' : ''));
        });
    }

    public function createReturn(Shipment $shipment)
    {
        if ($shipment->account_id !== auth()->user()->account_id) {
            abort(403, 'ليس لديك صلاحية لهذه الشحنة');
        }

        $return = Shipment::create([
            'account_id' => auth()->user()->account_id,
            'created_by' => auth()->id(),
            'reference_number' => Shipment::generateReference(),
            'source' => Shipment::SOURCE_RETURN,
            'status' => 'draft',
            'carrier_code' => $shipment->carrier_code,
            'carrier_name' => $shipment->carrier_name ?? $shipment->carrier_code,
            'service_code' => 'standard',
            'service_name' => 'Standard',
            'tracking_number' => 'RT-' . strtoupper(uniqid()),
            'carrier_shipment_id' => 'RT0060' . rand(1000000, 9999999),
            'sender_name' => $shipment->recipient_name,
            'sender_phone' => $shipment->recipient_phone ?? '—',
            'sender_address_1' => $shipment->recipient_address_1 ?? '—',
            'sender_city' => $shipment->recipient_city,
            'sender_country' => $shipment->recipient_country ?? 'SA',
            'recipient_name' => $shipment->sender_name ?? $shipment->recipient_name,
            'recipient_phone' => $shipment->sender_phone ?? '—',
            'recipient_address_1' => $shipment->sender_address_1 ?? '—',
            'recipient_city' => $shipment->sender_city,
            'recipient_country' => $shipment->sender_country ?? 'SA',
            'total_weight' => $shipment->total_weight ?? 0,
            'total_charge' => 0,
            'currency' => $shipment->currency ?? 'SAR',
            'metadata' => ['notes' => 'مرتجع — ' . $shipment->tracking_number],
        ]);

        return redirect()->route('shipments.show', $return)->with('success', 'تم إنشاء المرتجع');
    }

    public function label(Shipment $shipment)
    {
        if ($shipment->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        return view('pages.shipments.show', compact('shipment'))
            ->with('printMode', true);
    }

    public function export(Request $request): Response
    {
        $accountId = auth()->user()->account_id;

        $query = Shipment::where('account_id', $accountId);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', '%' . $search . '%')
                    ->orWhere('carrier_shipment_id', 'like', '%' . $search . '%');
            });
        }

        $shipments = $query->latest()->limit(5000)->get();
        $writer = Writer::createFromString('');
        $writer->insertOne(['رقم التتبع', 'الناقل', 'الحالة', 'المدينة', 'التكلفة', 'التاريخ']);

        foreach ($shipments as $s) {
            $writer->insertOne([
                $s->tracking_number ?? '',
                $s->carrier_code ?? '',
                $s->status ?? '',
                ($s->sender_city ?? '') . '→' . ($s->recipient_city ?? ''),
                $s->total_charge ?? 0,
                $s->created_at?->format('Y-m-d H:i') ?? '',
            ]);
        }

        $csvUtf8 = $writer->toString();
        $csvExcel = "\xFF\xFE" . mb_convert_encoding($csvUtf8, 'UTF-16LE', 'UTF-8');
        $filename = 'shipments-' . now()->format('Y-m-d-His') . '.csv';

        return response($csvExcel, 200, [
            'Content-Type' => 'text/csv; charset=UTF-16LE',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
