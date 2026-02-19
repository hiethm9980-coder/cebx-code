<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\User;
use App\Models\Role;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use App\Models\PaymentMethod;
use App\Models\AuditLog;
use App\Services\WalletBillingService;
use App\Services\AuditService;
use App\Exceptions\BusinessException;

/**
 * FR-IAM-017 + FR-IAM-019 + FR-IAM-020: Unit Tests (26 tests)
 */
class WalletBillingTest extends TestCase
{
    use RefreshDatabase;

    protected WalletBillingService $service;
    protected Account $account;
    protected User $owner;
    protected User $financeUser;
    protected User $viewer;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        AuditService::resetRequestId();
        $this->service = app(WalletBillingService::class);

        $this->account = Account::factory()->create();
        $this->owner = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => true,
        ]);

        // Finance role: full wallet + billing
        $financeRole = Role::factory()->create([
            'account_id'  => $this->account->id,
            'permissions' => ['wallet:balance', 'wallet:ledger', 'wallet:topup', 'wallet:configure', 'billing:view', 'billing:manage'],
        ]);
        $this->financeUser = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
            'role_id'    => $financeRole->id,
        ]);

        // Viewer: balance only
        $viewerRole = Role::factory()->create([
            'account_id'  => $this->account->id,
            'permissions' => ['wallet:balance'],
        ]);
        $this->viewer = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
            'role_id'    => $viewerRole->id,
        ]);

        $this->member = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Wallet: Get/Auto-Create
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function wallet_is_auto_created_on_first_access()
    {
        $wallet = $this->service->getWallet($this->account->id, $this->owner);

        $this->assertArrayHasKey('id', $wallet);
        $this->assertEquals('SAR', $wallet['currency']);
        $this->assertEquals('active', $wallet['status']);
    }

    /** @test */
    public function owner_sees_full_balance_details()
    {
        Wallet::factory()->withBalance(500)->create(['account_id' => $this->account->id]);

        $wallet = $this->service->getWallet($this->account->id, $this->owner);

        $this->assertArrayHasKey('available_balance', $wallet);
        $this->assertEquals(500, (float) $wallet['available_balance']);
    }

    /** @test */
    public function viewer_with_balance_permission_sees_details()
    {
        Wallet::factory()->withBalance(100)->create(['account_id' => $this->account->id]);

        $wallet = $this->service->getWallet($this->account->id, $this->viewer);

        $this->assertArrayHasKey('available_balance', $wallet);
    }

    /** @test */
    public function member_without_permission_sees_masked_summary()
    {
        Wallet::factory()->withBalance(100)->create(['account_id' => $this->account->id]);

        $wallet = $this->service->getWallet($this->account->id, $this->member);

        $this->assertArrayNotHasKey('available_balance', $wallet);
        $this->assertArrayHasKey('has_sufficient_balance', $wallet);
        $this->assertTrue($wallet['has_sufficient_balance']);
    }

    // ═══════════════════════════════════════════════════════════════
    // Wallet: Top-up
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_topup()
    {
        $entry = $this->service->recordTopUp(
            $this->account->id, 500.00, 'REF-001', $this->owner
        );

        $this->assertEquals(500, (float) $entry->amount);
        $this->assertEquals('topup', $entry->type);

        $wallet = Wallet::where('account_id', $this->account->id)->first();
        $this->assertEquals(500, (float) $wallet->available_balance);
    }

    /** @test */
    public function finance_user_can_topup()
    {
        $entry = $this->service->recordTopUp(
            $this->account->id, 200.00, 'REF-002', $this->financeUser
        );

        $this->assertEquals(200, (float) $entry->amount);
    }

    /** @test */
    public function member_cannot_topup()
    {
        $this->expectException(BusinessException::class);
        $this->service->recordTopUp($this->account->id, 100, 'REF-X', $this->member);
    }

    /** @test */
    public function topup_zero_amount_rejected()
    {
        $this->expectException(BusinessException::class);
        $this->service->recordTopUp($this->account->id, 0, 'REF-Z', $this->owner);
    }

    /** @test */
    public function topup_creates_ledger_entry()
    {
        $this->service->recordTopUp($this->account->id, 300, 'REF-003', $this->owner);

        $entries = WalletLedgerEntry::all();
        $this->assertCount(1, $entries);
        $this->assertEquals('topup', $entries->first()->type);
        $this->assertEquals(300, (float) $entries->first()->running_balance);
    }

    /** @test */
    public function topup_is_audit_logged()
    {
        $this->service->recordTopUp($this->account->id, 100, 'REF-AL', $this->owner);

        $log = AuditLog::withoutGlobalScopes()
            ->where('action', 'wallet.topup')
            ->first();

        $this->assertNotNull($log);
    }

    // ═══════════════════════════════════════════════════════════════
    // Wallet: Debit
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function debit_reduces_balance()
    {
        Wallet::factory()->withBalance(1000)->create(['account_id' => $this->account->id]);

        $entry = $this->service->recordDebit(
            $this->account->id, 250, 'shipment', 'SHP-001', $this->owner
        );

        $this->assertEquals(-250, (float) $entry->amount);
        $wallet = Wallet::where('account_id', $this->account->id)->first();
        $this->assertEquals(750, (float) $wallet->available_balance);
    }

    /** @test */
    public function debit_fails_on_insufficient_balance()
    {
        Wallet::factory()->withBalance(50)->create(['account_id' => $this->account->id]);

        $this->expectException(BusinessException::class);
        $this->service->recordDebit($this->account->id, 100, 'shipment', 'SHP-X', $this->owner);
    }

    /** @test */
    public function debit_fails_on_frozen_wallet()
    {
        Wallet::factory()->frozen()->withBalance(1000)->create(['account_id' => $this->account->id]);

        $this->expectException(BusinessException::class);
        $this->service->recordDebit($this->account->id, 50, 'shipment', 'SHP-F', $this->owner);
    }

    // ═══════════════════════════════════════════════════════════════
    // Wallet: Threshold
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_configure_threshold()
    {
        $wallet = $this->service->configureThreshold($this->account->id, 200.00, $this->owner);

        $this->assertEquals(200, (float) $wallet['low_balance_threshold']);
    }

    /** @test */
    public function low_balance_alert_triggers_on_debit()
    {
        $w = Wallet::factory()->withBalance(300)->withThreshold(250)->create(['account_id' => $this->account->id]);

        $this->service->recordDebit($this->account->id, 100, 'shipment', 'SHP-T', $this->owner);

        $alert = AuditLog::withoutGlobalScopes()
            ->where('action', 'wallet.low_balance_alert')
            ->first();

        $this->assertNotNull($alert);
    }

    /** @test */
    public function member_cannot_configure_threshold()
    {
        $this->expectException(BusinessException::class);
        $this->service->configureThreshold($this->account->id, 100, $this->member);
    }

    // ═══════════════════════════════════════════════════════════════
    // Wallet: Ledger
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function finance_user_can_view_ledger()
    {
        Wallet::factory()->withBalance(1000)->create(['account_id' => $this->account->id]);
        $this->service->recordTopUp($this->account->id, 500, 'REF-L1', $this->owner);
        $this->service->recordDebit($this->account->id, 100, 'shipment', 'SHP-L1', $this->owner);

        $ledger = $this->service->getLedger($this->account->id, $this->financeUser);

        $this->assertCount(2, $ledger['entries']);
    }

    /** @test */
    public function member_cannot_view_ledger()
    {
        $this->expectException(BusinessException::class);
        $this->service->getLedger($this->account->id, $this->member);
    }

    // ═══════════════════════════════════════════════════════════════
    // Payment Methods (Billing) — FR-IAM-017
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function finance_user_can_add_payment_method()
    {
        $method = $this->service->addPaymentMethod(
            $this->account->id,
            ['provider' => 'visa', 'last_four' => '4242', 'expiry_month' => '12', 'expiry_year' => '2028', 'cardholder_name' => 'Test'],
            $this->financeUser
        );

        $this->assertEquals('visa', $method->provider);
        $this->assertTrue($method->is_default); // First method is default
    }

    /** @test */
    public function member_cannot_add_payment_method()
    {
        $this->expectException(BusinessException::class);
        $this->service->addPaymentMethod($this->account->id, ['provider' => 'visa'], $this->member);
    }

    /** @test */
    public function finance_user_can_list_payment_methods()
    {
        PaymentMethod::factory()->count(2)->create(['account_id' => $this->account->id]);

        $methods = $this->service->listPaymentMethods($this->account->id, $this->financeUser);

        $this->assertCount(2, $methods);
    }

    /** @test */
    public function finance_user_can_remove_payment_method()
    {
        $method = PaymentMethod::factory()->create(['account_id' => $this->account->id]);

        $this->service->removePaymentMethod($this->account->id, $method->id, $this->financeUser);

        $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-020: Disabled Account Masking
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function payment_data_masked_when_account_suspended()
    {
        PaymentMethod::factory()->count(3)->create(['account_id' => $this->account->id]);

        $count = $this->service->maskPaymentDataForDisabledAccount($this->account->id);

        $this->assertEquals(3, $count);

        $allMasked = PaymentMethod::withoutGlobalScopes()
            ->where('account_id', $this->account->id)
            ->where('is_masked_override', true)
            ->where('is_active', false)
            ->count();

        $this->assertEquals(3, $allMasked);
    }

    /** @test */
    public function masked_cards_show_redacted_data()
    {
        $this->account->update(['status' => 'suspended']);
        $method = PaymentMethod::factory()->create([
            'account_id' => $this->account->id,
            'provider'   => 'visa',
            'last_four'  => '4242',
        ]);

        $safeData = $method->toSafeArray(accountDisabled: true);

        $this->assertTrue($safeData['is_masked']);
        $this->assertEquals('••••', $safeData['last_four']);
        $this->assertEquals('••••', $safeData['provider']);
        $this->assertEquals('•••••••••', $safeData['cardholder']);
        $this->assertEquals('account_disabled', $safeData['mask_reason']);
    }

    /** @test */
    public function payment_data_restored_on_reactivation()
    {
        PaymentMethod::factory()->masked()->count(2)->create(['account_id' => $this->account->id]);

        $count = $this->service->restorePaymentDataForReactivatedAccount($this->account->id);

        $this->assertEquals(2, $count);

        $restored = PaymentMethod::withoutGlobalScopes()
            ->where('account_id', $this->account->id)
            ->where('is_masked_override', false)
            ->count();

        $this->assertEquals(2, $restored);
    }

    /** @test */
    public function cannot_add_payment_method_to_disabled_account()
    {
        $this->account->update(['status' => 'suspended']);

        $this->expectException(BusinessException::class);
        $this->service->addPaymentMethod($this->account->id, ['provider' => 'visa'], $this->owner);
    }

    // ═══════════════════════════════════════════════════════════════
    // Permission List
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function permissions_list_contains_six_permissions()
    {
        $perms = WalletBillingService::walletPermissions();

        $this->assertArrayHasKey('wallet:balance', $perms);
        $this->assertArrayHasKey('wallet:ledger', $perms);
        $this->assertArrayHasKey('wallet:topup', $perms);
        $this->assertArrayHasKey('wallet:configure', $perms);
        $this->assertArrayHasKey('billing:view', $perms);
        $this->assertArrayHasKey('billing:manage', $perms);
    }
}
