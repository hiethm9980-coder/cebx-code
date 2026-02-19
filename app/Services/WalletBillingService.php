<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\DB;

/**
 * WalletBillingService
 *
 * FR-IAM-017: Separate wallet/billing permissions
 * FR-IAM-019: RBAC for wallet (top-up, view ledger, view balance, configure threshold)
 * FR-IAM-020: Mask payment card data when account disabled
 *
 * Wallet Permissions (granular):
 * - wallet:balance     → View balance only
 * - wallet:ledger      → View ledger/transactions
 * - wallet:topup       → Initiate top-up
 * - wallet:configure   → Set threshold, manage wallet settings
 * - billing:view       → View payment methods
 * - billing:manage     → Add/remove payment methods
 * Owner has ALL permissions implicitly.
 */
class WalletBillingService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    // ═══════════════════════════════════════════════════════════════
    // Wallet Operations
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get or create wallet for an account (auto-created on first access).
     */
    public function getWallet(string $accountId, User $performer): array
    {
        $canViewBalance = $this->canPerform($performer, 'wallet:balance');
        $wallet = $this->ensureWallet($accountId);

        $this->auditService->info(
            $accountId, $performer->id,
            'wallet.viewed', AuditLog::CATEGORY_FINANCIAL,
            'Wallet', $wallet->id
        );

        return $wallet->summary($canViewBalance);
    }

    /**
     * Get ledger entries (paginated).
     */
    public function getLedger(string $accountId, User $performer, array $filters = []): array
    {
        $this->assertPermission($performer, 'wallet:ledger');

        $wallet = $this->ensureWallet($accountId);
        $query = $wallet->ledgerEntries();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $entries = $query->limit($filters['limit'] ?? 50)->get();

        $this->auditService->info(
            $accountId, $performer->id,
            'wallet.ledger_viewed', AuditLog::CATEGORY_FINANCIAL,
            'Wallet', $wallet->id,
            null, null,
            ['entries_returned' => $entries->count(), 'filters' => $filters]
        );

        return [
            'wallet'  => $wallet->summary(true),
            'entries' => $entries->map(fn ($e) => [
                'id'              => $e->id,
                'type'            => $e->type,
                'type_label'      => $e->typeLabel(),
                'amount'          => $e->amount,
                'running_balance' => $e->running_balance,
                'reference_type'  => $e->reference_type,
                'reference_id'    => $e->reference_id,
                'description'     => $e->description,
                'actor_user_id'   => $e->actor_user_id,
                'created_at'      => $e->created_at?->toISOString(),
            ])->toArray(),
        ];
    }

    /**
     * Record a top-up (credit).
     */
    public function recordTopUp(
        string $accountId,
        float  $amount,
        string $referenceId,
        User   $performer,
        ?string $description = null,
        ?array $metadata = null
    ): WalletLedgerEntry {
        $this->assertPermission($performer, 'wallet:topup');

        if ($amount <= 0) {
            throw new BusinessException('المبلغ يجب أن يكون أكبر من صفر.', 'ERR_INVALID_AMOUNT', 422);
        }

        $wallet = $this->ensureWallet($accountId);

        if (!$wallet->isActive()) {
            throw new BusinessException('المحفظة مجمدة أو مغلقة.', 'ERR_WALLET_FROZEN', 422);
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceId, $performer, $description, $metadata) {
            $wallet->increment('available_balance', $amount);
            $wallet->refresh();

            $entry = WalletLedgerEntry::create([
                'wallet_id'       => $wallet->id,
                'type'            => WalletLedgerEntry::TYPE_TOPUP,
                'amount'          => $amount,
                'running_balance' => $wallet->available_balance,
                'reference_type'  => 'topup',
                'reference_id'    => $referenceId,
                'actor_user_id'   => $performer->id,
                'description'     => $description ?? 'شحن رصيد',
                'metadata'        => $metadata,
                'created_at'      => now(),
            ]);

            $this->auditService->info(
                $wallet->account_id, $performer->id,
                'wallet.topup', AuditLog::CATEGORY_FINANCIAL,
                'WalletLedgerEntry', $entry->id,
                ['balance_before' => $wallet->available_balance - $amount],
                ['balance_after' => $wallet->available_balance, 'amount' => $amount],
                ['reference_id' => $referenceId]
            );

            return $entry;
        });
    }

    /**
     * Record a debit (charge for shipment, etc.)
     */
    public function recordDebit(
        string $accountId,
        float  $amount,
        string $referenceType,
        string $referenceId,
        User   $performer,
        ?string $description = null
    ): WalletLedgerEntry {
        $wallet = $this->ensureWallet($accountId);

        if (!$wallet->isActive()) {
            throw new BusinessException('المحفظة مجمدة أو مغلقة.', 'ERR_WALLET_FROZEN', 422);
        }

        if ((float) $wallet->available_balance < $amount) {
            throw new BusinessException('رصيد المحفظة غير كافٍ.', 'ERR_INSUFFICIENT_BALANCE', 422);
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceType, $referenceId, $performer, $description) {
            $wallet->decrement('available_balance', $amount);
            $wallet->refresh();

            $entry = WalletLedgerEntry::create([
                'wallet_id'       => $wallet->id,
                'type'            => WalletLedgerEntry::TYPE_DEBIT,
                'amount'          => -$amount,
                'running_balance' => $wallet->available_balance,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'actor_user_id'   => $performer->id,
                'description'     => $description ?? 'خصم',
                'created_at'      => now(),
            ]);

            $this->auditService->info(
                $wallet->account_id, $performer->id,
                'wallet.debit', AuditLog::CATEGORY_FINANCIAL,
                'WalletLedgerEntry', $entry->id,
                ['balance_before' => $wallet->available_balance + $amount],
                ['balance_after' => $wallet->available_balance, 'amount' => -$amount]
            );

            // Check threshold alert
            if ($wallet->isBelowThreshold()) {
                $this->auditService->warning(
                    $wallet->account_id, $performer->id,
                    'wallet.low_balance_alert', AuditLog::CATEGORY_FINANCIAL,
                    'Wallet', $wallet->id,
                    null, null,
                    ['balance' => $wallet->available_balance, 'threshold' => $wallet->low_balance_threshold]
                );
            }

            return $entry;
        });
    }

    /**
     * Configure low-balance threshold.
     */
    public function configureThreshold(string $accountId, ?float $threshold, User $performer): array
    {
        $this->assertPermission($performer, 'wallet:configure');

        $wallet = $this->ensureWallet($accountId);
        $old = $wallet->low_balance_threshold;

        $wallet->update(['low_balance_threshold' => $threshold]);

        $this->auditService->info(
            $accountId, $performer->id,
            'wallet.threshold_updated', AuditLog::CATEGORY_FINANCIAL,
            'Wallet', $wallet->id,
            ['threshold' => $old],
            ['threshold' => $threshold]
        );

        return $wallet->fresh()->summary(true);
    }

    // ═══════════════════════════════════════════════════════════════
    // Payment Methods (Billing) — FR-IAM-017 + FR-IAM-020
    // ═══════════════════════════════════════════════════════════════

    /**
     * List payment methods (respects FR-IAM-020 masking).
     */
    public function listPaymentMethods(string $accountId, User $performer): array
    {
        $this->assertPermission($performer, 'billing:view');

        $account = Account::findOrFail($accountId);
        $accountDisabled = in_array($account->status, ['suspended', 'closed']);

        $methods = PaymentMethod::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        $this->auditService->info(
            $accountId, $performer->id,
            'billing.methods_viewed', AuditLog::CATEGORY_FINANCIAL,
            null, null, null, null,
            ['count' => $methods->count(), 'account_disabled' => $accountDisabled]
        );

        return $methods->map(fn (PaymentMethod $m) => $m->toSafeArray($accountDisabled))->toArray();
    }

    /**
     * Add a payment method.
     */
    public function addPaymentMethod(string $accountId, array $data, User $performer): PaymentMethod
    {
        $this->assertPermission($performer, 'billing:manage');

        $account = Account::findOrFail($accountId);
        if (in_array($account->status, ['suspended', 'closed'])) {
            throw new BusinessException(
                'لا يمكن إضافة وسيلة دفع لحساب معطل.',
                'ERR_ACCOUNT_DISABLED', 422
            );
        }

        return DB::transaction(function () use ($accountId, $data, $performer) {
            $isFirst = !PaymentMethod::withoutGlobalScopes()
                ->where('account_id', $accountId)
                ->where('is_active', true)
                ->exists();

            $method = PaymentMethod::create([
                'account_id'          => $accountId,
                'type'                => $data['type'] ?? PaymentMethod::TYPE_CARD,
                'label'               => $data['label'] ?? null,
                'provider'            => $data['provider'] ?? null,
                'last_four'           => $data['last_four'] ?? null,
                'expiry_month'        => $data['expiry_month'] ?? null,
                'expiry_year'         => $data['expiry_year'] ?? null,
                'cardholder_name'     => $data['cardholder_name'] ?? null,
                'gateway_token'       => $data['gateway_token'] ?? null,
                'gateway_customer_id' => $data['gateway_customer_id'] ?? null,
                'is_default'          => $isFirst,
                'is_active'           => true,
                'added_by'            => $performer->id,
            ]);

            $this->auditService->info(
                $accountId, $performer->id,
                'billing.method_added', AuditLog::CATEGORY_FINANCIAL,
                'PaymentMethod', $method->id,
                null,
                ['type' => $method->type, 'provider' => $method->provider, 'last_four' => $method->last_four]
            );

            return $method;
        });
    }

    /**
     * Remove (soft-delete) a payment method.
     */
    public function removePaymentMethod(string $accountId, string $methodId, User $performer): void
    {
        $this->assertPermission($performer, 'billing:manage');

        $method = PaymentMethod::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('id', $methodId)
            ->firstOrFail();

        $method->update(['is_active' => false]);
        $method->delete();

        $this->auditService->warning(
            $accountId, $performer->id,
            'billing.method_removed', AuditLog::CATEGORY_FINANCIAL,
            'PaymentMethod', $method->id,
            ['provider' => $method->provider, 'last_four' => $method->last_four],
            null
        );
    }

    /**
     * FR-IAM-020: Mask all payment methods when account is disabled.
     * Called when account status changes to suspended/closed.
     */
    public function maskPaymentDataForDisabledAccount(string $accountId): int
    {
        $count = PaymentMethod::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('is_masked_override', false)
            ->update([
                'is_masked_override' => true,
                'is_active'          => false,
            ]);

        if ($count > 0) {
            $this->auditService->warning(
                $accountId, null,
                'billing.payment_data_masked', AuditLog::CATEGORY_FINANCIAL,
                'Account', $accountId,
                null,
                ['masked_count' => $count, 'reason' => 'account_disabled']
            );
        }

        return $count;
    }

    /**
     * FR-IAM-020: Restore payment methods on account reactivation.
     * Marks methods for re-validation but doesn't auto-activate.
     */
    public function restorePaymentDataForReactivatedAccount(string $accountId): int
    {
        $count = PaymentMethod::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('is_masked_override', true)
            ->update(['is_masked_override' => false]);

        if ($count > 0) {
            $this->auditService->info(
                $accountId, null,
                'billing.payment_data_restored', AuditLog::CATEGORY_FINANCIAL,
                'Account', $accountId,
                null,
                ['restored_count' => $count, 'note' => 'Methods unmasked but remain inactive until re-validated']
            );
        }

        return $count;
    }

    // ═══════════════════════════════════════════════════════════════
    // Supported Permissions List (for RBAC setup)
    // ═══════════════════════════════════════════════════════════════

    public static function walletPermissions(): array
    {
        return [
            'wallet:balance'   => 'عرض رصيد المحفظة',
            'wallet:ledger'    => 'عرض كشف الحساب',
            'wallet:topup'     => 'شحن الرصيد',
            'wallet:configure' => 'إعدادات المحفظة (حد التنبيه)',
            'billing:view'     => 'عرض وسائل الدفع',
            'billing:manage'   => 'إضافة/إزالة وسائل الدفع',
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    // Internal Helpers
    // ═══════════════════════════════════════════════════════════════

    private function ensureWallet(string $accountId): Wallet
    {
        return Wallet::firstOrCreate(
            ['account_id' => $accountId],
            [
                'currency'          => Account::findOrFail($accountId)->currency ?? 'SAR',
                'available_balance' => 0,
                'locked_balance'    => 0,
                'status'            => Wallet::STATUS_ACTIVE,
            ]
        );
    }

    private function canPerform(User $user, string $permission): bool
    {
        return $user->is_owner || $user->hasPermission($permission);
    }

    private function assertPermission(User $user, string $permission): void
    {
        if (!$this->canPerform($user, $permission)) {
            $this->auditService->warning(
                $user->account_id, $user->id,
                'wallet.access_denied', AuditLog::CATEGORY_FINANCIAL,
                null, null, null, null,
                ['required_permission' => $permission]
            );
            throw BusinessException::permissionDenied();
        }
    }
}
