<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Claim;
use App\Models\ShipmentCharge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * CBEX GROUP — Insurance Controller
 *
 * Manages shipment insurance: quoting, purchasing, claims filing.
 */
class InsuranceController extends Controller
{
    protected array $rates = [
        'basic'   => ['rate' => 1.5,  'max_coverage' => 50000,  'deductible' => 500],
        'premium' => ['rate' => 2.5,  'max_coverage' => 200000, 'deductible' => 250],
        'full'    => ['rate' => 4.0,  'max_coverage' => 1000000, 'deductible' => 0],
    ];

    /**
     * Get insurance quote for a shipment
     */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'declared_value' => 'required|numeric|min:1',
            'shipment_type' => 'required|in:air,sea,land',
            'destination_country' => 'required|string|size:2',
            'dangerous_goods' => 'boolean',
        ]);

        $value = $data['declared_value'];
        $dgMultiplier = ($data['dangerous_goods'] ?? false) ? 1.5 : 1.0;
        $typeMultiplier = match ($data['shipment_type']) {
            'sea' => 1.3, 'land' => 1.1, default => 1.0,
        };

        $quotes = [];
        foreach ($this->rates as $plan => $config) {
            $premium = round($value * ($config['rate'] / 100) * $dgMultiplier * $typeMultiplier, 2);
            $coverage = min($value, $config['max_coverage']);

            $quotes[] = [
                'plan' => $plan,
                'plan_label' => match ($plan) {
                    'basic' => 'تأمين أساسي', 'premium' => 'تأمين متقدم', 'full' => 'تأمين شامل',
                },
                'premium' => $premium,
                'coverage' => $coverage,
                'deductible' => $config['deductible'],
                'rate_percent' => round($config['rate'] * $dgMultiplier * $typeMultiplier, 2),
                'currency' => 'SAR',
            ];
        }

        return response()->json(['data' => ['quotes' => $quotes, 'declared_value' => $value]]);
    }

    /**
     * Purchase insurance for a shipment
     */
    public function purchase(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'plan' => 'required|in:basic,premium,full',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($shipmentId);

        if (!$shipment->declared_value || $shipment->declared_value <= 0) {
            return response()->json(['message' => 'يجب تحديد القيمة المصرح بها أولاً'], 422);
        }

        $config = $this->rates[$data['plan']];
        $premium = round($shipment->declared_value * ($config['rate'] / 100), 2);
        $coverage = min($shipment->declared_value, $config['max_coverage']);

        // Add insurance charge
        ShipmentCharge::create([
            'id' => Str::uuid(),
            'shipment_id' => $shipment->id,
            'charge_type' => 'insurance',
            'description' => "تأمين شحنة — خطة {$data['plan']}",
            'amount' => $premium,
            'currency' => 'SAR',
            'status' => 'pending',
        ]);

        $shipment->update([
            'insurance_flag' => true,
            'insurance_plan' => $data['plan'],
            'insurance_coverage' => $coverage,
            'insurance_premium' => $premium,
        ]);

        return response()->json([
            'data' => [
                'plan' => $data['plan'],
                'premium' => $premium,
                'coverage' => $coverage,
                'deductible' => $config['deductible'],
            ],
            'message' => 'تم تفعيل التأمين بنجاح',
        ]);
    }

    /**
     * File an insurance claim
     */
    public function fileClaim(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'claim_type' => 'required|in:damage,loss,delay,theft',
            'description' => 'required|string|max:2000',
            'claimed_amount' => 'required|numeric|min:1',
        ]);

        $shipment = Shipment::where('account_id', $request->user()->account_id)->findOrFail($shipmentId);

        if (!$shipment->insurance_flag) {
            return response()->json(['message' => 'الشحنة غير مؤمنة'], 422);
        }

        $claim = Claim::create([
            'id' => Str::uuid()->toString(),
            'account_id' => $request->user()->account_id,
            'shipment_id' => $shipment->id,
            'claim_number' => 'CLM-' . strtoupper(Str::random(8)),
            'claim_type' => $data['claim_type'],
            'description' => $data['description'],
            'claim_amount' => min($data['claimed_amount'], $shipment->insurance_coverage ?? $data['claimed_amount']),
            'status' => 'submitted',
            'filed_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $claim, 'message' => "تم تقديم المطالبة #{$claim->claim_number}"], 201);
    }
}
