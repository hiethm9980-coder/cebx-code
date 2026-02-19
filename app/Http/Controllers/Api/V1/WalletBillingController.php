<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WalletBillingController — FR-IAM-017 + FR-IAM-019 + FR-IAM-020
 *
 * Wallet: balance, ledger, top-up, threshold
 * Billing: payment methods CRUD, masking for disabled accounts
 */
class WalletBillingController extends Controller
{
    public function __construct(
        protected WalletBillingService $service
    ) {}

    // ─── Wallet ──────────────────────────────────────────────────

    /**
     * GET /api/v1/wallet
     */
    public function wallet(Request $request): JsonResponse
    {
        $wallet = $this->service->getWallet(
            $request->user()->account_id,
            $request->user()
        );

        return response()->json(['success' => true, 'data' => $wallet]);
    }

    /**
     * GET /api/v1/wallet/ledger
     */
    public function ledger(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'from', 'to', 'limit']);

        $data = $this->service->getLedger(
            $request->user()->account_id,
            $request->user(),
            $filters
        );

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * POST /api/v1/wallet/topup
     */
    public function topUp(Request $request): JsonResponse
    {
        $request->validate([
            'amount'       => 'required|numeric|min:1|max:999999',
            'reference_id' => 'required|string|max:100',
            'description'  => 'sometimes|string|max:500',
        ]);

        $entry = $this->service->recordTopUp(
            $request->user()->account_id,
            (float) $request->amount,
            $request->reference_id,
            $request->user(),
            $request->description
        );

        return response()->json([
            'success' => true,
            'message' => 'تم شحن الرصيد بنجاح.',
            'data'    => [
                'entry_id'        => $entry->id,
                'amount'          => $entry->amount,
                'running_balance' => $entry->running_balance,
            ],
        ], 201);
    }

    /**
     * PUT /api/v1/wallet/threshold
     */
    public function configureThreshold(Request $request): JsonResponse
    {
        $request->validate([
            'threshold' => 'nullable|numeric|min:0|max:999999',
        ]);

        $wallet = $this->service->configureThreshold(
            $request->user()->account_id,
            $request->threshold !== null ? (float) $request->threshold : null,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حد التنبيه.',
            'data'    => $wallet,
        ]);
    }

    // ─── Billing / Payment Methods ───────────────────────────────

    /**
     * GET /api/v1/billing/methods
     */
    public function paymentMethods(Request $request): JsonResponse
    {
        $methods = $this->service->listPaymentMethods(
            $request->user()->account_id,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data'    => $methods,
            'meta'    => ['count' => count($methods)],
        ]);
    }

    /**
     * POST /api/v1/billing/methods
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        $request->validate([
            'type'               => 'sometimes|in:card,bank_transfer,wallet_gateway',
            'label'              => 'sometimes|string|max:100',
            'provider'           => 'sometimes|string|max:50',
            'last_four'          => 'sometimes|string|size:4',
            'expiry_month'       => 'sometimes|string|size:2',
            'expiry_year'        => 'sometimes|string|size:4',
            'cardholder_name'    => 'sometimes|string|max:150',
            'gateway_token'      => 'sometimes|string|max:500',
            'gateway_customer_id' => 'sometimes|string|max:255',
        ]);

        $method = $this->service->addPaymentMethod(
            $request->user()->account_id,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة وسيلة الدفع.',
            'data'    => $method->toSafeArray(),
        ], 201);
    }

    /**
     * DELETE /api/v1/billing/methods/{id}
     */
    public function removePaymentMethod(Request $request, string $id): JsonResponse
    {
        $this->service->removePaymentMethod(
            $request->user()->account_id,
            $id,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إزالة وسيلة الدفع.',
        ]);
    }

    /**
     * GET /api/v1/wallet/permissions
     * Returns available wallet/billing permissions for RBAC setup.
     */
    public function permissions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => WalletBillingService::walletPermissions(),
        ]);
    }
}
