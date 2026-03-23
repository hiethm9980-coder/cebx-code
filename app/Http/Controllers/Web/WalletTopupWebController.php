<?php

namespace App\Http\Controllers\Web;

use App\Services\BillingWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WalletTopupWebController extends WebController
{
    public function __construct(
        private readonly BillingWalletService $billingWalletService
    ) {}

    public function topup(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $accountId = (string) $user->account_id;
        $currency = (string) ($user->account?->currency ?? 'SAR');

        $wallet = $this->billingWalletService->getWalletForAccount($accountId, $currency)
            ?? $this->billingWalletService->createWallet($accountId, $currency, (string) ($user->account?->id ?? $accountId));

        $topup = $this->billingWalletService->initiateTopup((string) $wallet->id, (float) $payload['amount'], [
            'payment_gateway' => 'manual',
            'payment_method' => $payload['payment_method'] ?? 'bank_transfer',
            'checkout_url' => route('wallet.index'),
            'initiated_by' => (string) $user->id,
            'idempotency_key' => (string) ($request->header('Idempotency-Key') ?? ('web-topup-' . Str::uuid())),
        ]);

        return redirect($topup->checkout_url ?: route('wallet.index'))
            ->with('success', 'Top-up request created successfully. Reference: ' . $topup->id);
    }
}

