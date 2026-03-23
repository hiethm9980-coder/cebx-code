<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\BusinessException;
use App\Models\Shipment;
use App\Services\ShipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Csv\Writer;

class ShipmentWebController extends WebController
{
    public function __construct(
        private readonly ShipmentService $shipmentService
    ) {}

    public function index(Request $request)
    {
        $query = Shipment::query()
            ->with(['order.store']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', '%' . $search . '%')
                  ->orWhere('carrier_shipment_id', 'like', '%' . $search . '%')
                  ->orWhere('recipient_name', 'like', '%' . $search . '%')
                  ->orWhere('reference_number', 'like', '%' . $search . '%');
            });
        }

        $shipments = $query->latest()->paginate(20)->withQueryString();
        $totalCount = Shipment::count();

        return view('pages.shipments.index', compact('shipments', 'totalCount'));
    }

    public function show(Shipment $shipment)
    {
        $statusToStep = [
            'draft' => 0, 'validated' => 0, 'rated' => 0, 'payment_pending' => 0,
            'purchased' => 0, 'ready_for_pickup' => 0, 'pending' => 0,
            'picked_up' => 1, 'in_transit' => 2, 'out_for_delivery' => 3,
            'delivered' => 4, 'returned' => 4, 'exception' => 2,
            'cancelled' => 0, 'failed' => 0,
        ];
        $currentIdx = $statusToStep[$shipment->status] ?? 0;

        $timeline = collect([
            ['title' => 'تم إنشاء الشحنة', 'date' => $shipment->created_at->format('Y-m-d H:i'), 'done' => true],
            ['title' => 'تم الاستلام', 'date' => $shipment->picked_up_at ?? '—', 'done' => $currentIdx >= 1],
            ['title' => 'في الطريق', 'date' => '—', 'done' => $currentIdx >= 2],
            ['title' => 'خارج للتسليم', 'date' => '—', 'done' => $currentIdx >= 3],
            ['title' => 'تم التسليم', 'date' => $shipment->actual_delivery_at ?? '—', 'done' => $currentIdx >= 4],
        ]);

        return view('pages.shipments.show', compact('shipment', 'timeline'));
    }

    /**
     * Store — يدعم نموذجين:
     * 1) النموذج الكامل (sender + recipient) من صفحة /shipments/create
     * 2) النموذج المختصر (modal) من صفحة index
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()
            ->route($this->portalShipmentCreateRoute())
            ->with('warning', 'Legacy shipment create is disabled. Please use the portal shipment workflow.');
    }

    public function cancel(Request $request, Shipment $shipment): RedirectResponse
    {
        try {
            $cancelled = $this->shipmentService->cancelShipment(
                accountId: (string) $request->user()->account_id,
                shipmentId: (string) $shipment->id,
                performer: $request->user(),
                reason: $request->input('reason')
            );
        } catch (BusinessException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('warning', 'Shipment cancelled: ' . ($cancelled->tracking_number ?? $shipment->tracking_number));
    }

    public function createReturn(Request $request, Shipment $shipment): RedirectResponse
    {
        try {
            $return = $this->shipmentService->createReturnShipment(
                accountId: (string) $request->user()->account_id,
                originalShipmentId: (string) $shipment->id,
                overrides: [],
                performer: $request->user()
            );
        } catch (BusinessException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('shipments.show', $return)->with('success', 'Return shipment created successfully.');
    }

    public function label(Shipment $shipment)
    {
        return view('pages.shipments.show', compact('shipment'))
            ->with('printMode', true);
    }

    public function export(Request $request): Response
    {
        $query = Shipment::query()
            ->where('account_id', auth()->user()->account_id);

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

        $shipments = $query->orderByDesc('created_at')->get();

        $writer = Writer::createFromString('');
        $writer->insertOne([
            'الرقم', 'المرجع', 'التتبع', 'الناقل', 'الحالة', 'المستلم',
            'مدينة المرسل', 'مدينة المستلم', 'الوزن', 'التكلفة', 'العملة', 'التاريخ',
        ]);
        foreach ($shipments as $s) {
            $writer->insertOne([
                $s->tracking_number, $s->reference_number,
                $s->carrier_shipment_id ?? '', $s->carrier_code ?? '',
                $s->status, $s->recipient_name,
                $s->sender_city ?? '', $s->recipient_city ?? '',
                $s->total_weight ?? '', $s->total_charge ?? '',
                $s->currency ?? 'SAR',
                $s->created_at?->format('Y-m-d H:i') ?? '',
            ]);
        }

        $csvUtf8  = $writer->toString();
        $csvExcel = "\xFF\xFE" . mb_convert_encoding($csvUtf8, 'UTF-16LE', 'UTF-8');
        $filename = 'shipments-' . now()->format('Y-m-d-His') . '.csv';

        return response($csvExcel, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-16LE',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function portalShipmentCreateRoute(): string
    {
        $account = auth()->user()?->account;

        if ($account && method_exists($account, 'isIndividual') && $account->isIndividual()) {
            return 'b2c.shipments.create';
        }

        return 'b2b.shipments.create';
    }
}
