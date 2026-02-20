<?php

namespace App\Http\Controllers\Web;

use App\Models\Shipment;
use App\Models\Address;
use App\Models\Parcel;
use Illuminate\Http\Request;
use League\Csv\Writer;

class ShipmentWebController extends WebController
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        $query = Shipment::where('account_id', $accountId)
            ->with(['order.store', 'parcels']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($carrier = $request->get('carrier')) {
            $query->where('carrier_code', $carrier);
        }
        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();

        return view('pages.shipments.index', compact('shipments'));
    }

    public function create()
    {
        $accountId = auth()->user()->account_id;
        $savedAddresses = Address::where('account_id', $accountId)->get();

        return view('pages.shipments.create', compact('savedAddresses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sender_name'       => 'required|string|max:200',
            'sender_phone'      => 'required|string|max:30',
            'sender_country'    => 'required|string|max:2',
            'sender_city'       => 'required|string|max:100',
            'sender_address_1'  => 'required|string|max:300',
            'recipient_name'    => 'required|string|max:200',
            'recipient_phone'   => 'required|string|max:30',
            'recipient_country' => 'required|string|max:2',
            'recipient_city'    => 'required|string|max:100',
            'recipient_address_1' => 'required|string|max:300',
            'weight'            => 'nullable|numeric|min:0',
            'length'            => 'nullable|numeric|min:0',
            'width'             => 'nullable|numeric|min:0',
            'height'            => 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:300',
        ]);

        $accountId = auth()->user()->account_id;
        $refNumber = 'SHP-' . date('Y') . '-' . str_pad(Shipment::where('account_id', $accountId)->count() + 1, 4, '0', STR_PAD_LEFT);

        $isInternational = $validated['sender_country'] !== $validated['recipient_country'];

        $shipment = Shipment::create([
            'account_id'         => $accountId,
            'reference_number'   => $refNumber,
            'source'             => 'direct',
            'status'             => 'draft',
            'sender_name'        => $validated['sender_name'],
            'sender_phone'       => $validated['sender_phone'],
            'sender_country'     => $validated['sender_country'],
            'sender_city'        => $validated['sender_city'],
            'sender_address_1'   => $validated['sender_address_1'],
            'recipient_name'     => $validated['recipient_name'],
            'recipient_phone'    => $validated['recipient_phone'],
            'recipient_country'  => $validated['recipient_country'],
            'recipient_city'     => $validated['recipient_city'],
            'recipient_address_1'=> $validated['recipient_address_1'],
            'total_weight'       => $validated['weight'] ?? 0,
            'is_international'   => $isInternational,
            'is_cod'             => $request->boolean('is_cod'),
            'is_insured'         => $request->boolean('is_insured'),
            'has_dangerous_goods'=> $request->boolean('has_dangerous_goods'),
            'parcels_count'      => $request->input('parcels_count', 1),
            'currency'           => 'SAR',
            'created_by'         => auth()->id(),
        ]);

        // Create parcel record
        if ($validated['weight'] || $validated['length']) {
            Parcel::create([
                'shipment_id'      => $shipment->id,
                'sequence'         => 1,
                'weight'           => $validated['weight'] ?? 0,
                'length'           => $validated['length'] ?? 0,
                'width'            => $validated['width'] ?? 0,
                'height'           => $validated['height'] ?? 0,
                'description'      => $validated['description'] ?? null,
            ]);
        }

        // Save sender address if requested (B2C)
        if ($request->boolean('save_sender_address')) {
            Address::create([
                'account_id'     => $accountId,
                'type'           => 'sender',
                'contact_name'   => $validated['sender_name'],
                'phone'          => $validated['sender_phone'],
                'country'        => $validated['sender_country'],
                'city'           => $validated['sender_city'],
                'address_line_1' => $validated['sender_address_1'],
            ]);
        }

        return redirect()->route('shipments.show', $shipment)->with('success', 'تم إنشاء الشحنة بنجاح');
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['parcels', 'statusHistory' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        // Build tracking history for timeline component
        $trackingHistory = $shipment->statusHistory->map(function ($history) {
            return [
                'title'    => $this->getStatusLabel($history->to_status),
                'date'     => $history->created_at->format('d/m/Y — h:i A'),
                'location' => $history->metadata['location'] ?? null,
                'desc'     => $history->reason ?? null,
            ];
        })->toArray();

        // If no history, create default
        if (empty($trackingHistory)) {
            $trackingHistory = [
                ['title' => $this->getStatusLabel($shipment->status), 'date' => $shipment->updated_at->format('d/m/Y — h:i A')],
                ['title' => 'تم إنشاء الشحنة', 'date' => $shipment->created_at->format('d/m/Y — h:i A')],
            ];
        }

        return view('pages.shipments.show', compact('shipment', 'trackingHistory'));
    }

    public function cancel(Shipment $shipment)
    {
        $shipment->update([
            'status'             => 'cancelled',
            'cancelled_by'       => auth()->id(),
            'cancellation_reason'=> 'إلغاء من المستخدم',
        ]);

        return back()->with('warning', 'تم إلغاء الشحنة');
    }

    public function createReturn(Shipment $shipment)
    {
        // Create a return shipment
        $return = $shipment->replicate();
        $return->fill([
            'reference_number' => 'RET-' . $shipment->reference_number,
            'source'           => 'return',
            'is_return'        => true,
            'status'           => 'draft',
            // Swap sender/recipient
            'sender_name'      => $shipment->recipient_name,
            'sender_phone'     => $shipment->recipient_phone,
            'sender_city'      => $shipment->recipient_city,
            'sender_address_1' => $shipment->recipient_address_1,
            'recipient_name'   => $shipment->sender_name,
            'recipient_phone'  => $shipment->sender_phone,
            'recipient_city'   => $shipment->sender_city,
            'recipient_address_1' => $shipment->sender_address_1,
        ]);
        $return->save();

        return redirect()->route('shipments.show', $return)->with('success', 'تم إنشاء شحنة الإرجاع');
    }

    public function label(Shipment $shipment)
    {
        if ($shipment->label_url) {
            return redirect($shipment->label_url);
        }

        return back()->with('error', 'لا يوجد ملصق متاح لهذه الشحنة');
    }

    public function export(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $shipments = Shipment::where('account_id', $accountId)->latest()->get();

        $csv = Writer::createFromString('');
        $csv->insertOne(['رقم التتبع', 'المستلم', 'الناقل', 'المدينة', 'الحالة', 'التاريخ']);

        foreach ($shipments as $s) {
            $csv->insertOne([
                $s->reference_number,
                $s->recipient_name,
                $s->carrier_code,
                $s->recipient_city,
                $s->status,
                $s->created_at->format('Y-m-d'),
            ]);
        }

        return response($csv->toString(), 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="shipments-export.csv"',
        ]);
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft'            => 'تم إنشاء الشحنة',
            'validated'        => 'تم التحقق',
            'rated'            => 'تم التسعير',
            'payment_pending'  => 'بانتظار الدفع',
            'purchased'        => 'تم شراء الخدمة',
            'ready_for_pickup' => 'جاهزة للاستلام',
            'picked_up'        => 'تم الاستلام من المرسل',
            'in_transit'       => 'في الطريق',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered'        => 'تم التسليم',
            'returned'         => 'تم الإرجاع',
            'exception'        => 'استثناء',
            'cancelled'        => 'ملغي',
            'failed'           => 'فشل',
            default            => $status,
        };
    }
}
