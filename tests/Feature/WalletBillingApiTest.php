<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\User;
use App\Models\Role;
use App\Models\Wallet;
use App\Models\PaymentMethod;
use App\Models\AuditLog;
use App\Services\AuditService;

/**
 * FR-IAM-017 + FR-IAM-019 + FR-IAM-020: Integration Tests (20 tests)
 */
class WalletBillingApiTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $owner;
    protected User $financeUser;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();
        AuditService::resetRequestId();

        $this->account = Account::factory()->create();
        $this->owner = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => true,
        ]);

        $financeRole = Role::factory()->create([
            'account_id'  => $this->account->id,
            'permissions' => ['wallet:balance', 'wallet:ledger', 'wallet:topup', 'wallet:configure', 'billing:view', 'billing:manage'],
        ]);
        $this->financeUser = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
            'role_id'    => $financeRole->id,
        ]);

        $this->member = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /wallet
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_gets_full_wallet_details()
    {
        Wallet::factory()->withBalance(500)->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonPath('data.currency', 'SAR')
            ->assertJsonPath('data.available_balance', '500.00');
    }

    /** @test */
    public function member_gets_masked_wallet()
    {
        Wallet::factory()->withBalance(500)->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonMissing(['available_balance'])
            ->assertJsonPath('data.has_sufficient_balance', true);
    }

    /** @test */
    public function wallet_auto_created_on_first_access()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/wallet');

        $response->assertOk();
        $this->assertDatabaseHas('wallets', ['account_id' => $this->account->id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /wallet/topup
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_topup()
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/wallet/topup', [
                'amount'       => 500,
                'reference_id' => 'PAY-001',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.amount', '500.00');
    }

    /** @test */
    public function finance_user_can_topup()
    {
        $response = $this->actingAs($this->financeUser)
            ->postJson('/api/v1/wallet/topup', [
                'amount'       => 200,
                'reference_id' => 'PAY-002',
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function member_cannot_topup()
    {
        $response = $this->actingAs($this->member)
            ->postJson('/api/v1/wallet/topup', [
                'amount'       => 100,
                'reference_id' => 'PAY-X',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function topup_validates_amount()
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/wallet/topup', [
                'amount'       => -10,
                'reference_id' => 'PAY-NEG',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function topup_creates_audit_log()
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/wallet/topup', [
                'amount'       => 100,
                'reference_id' => 'PAY-AL',
            ]);

        $log = AuditLog::withoutGlobalScopes()
            ->where('action', 'wallet.topup')
            ->first();

        $this->assertNotNull($log);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /wallet/ledger
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function finance_user_can_view_ledger()
    {
        Wallet::factory()->withBalance(1000)->create(['account_id' => $this->account->id]);

        // Create some entries
        $this->actingAs($this->owner)->postJson('/api/v1/wallet/topup', ['amount' => 500, 'reference_id' => 'R1']);

        $response = $this->actingAs($this->financeUser)
            ->getJson('/api/v1/wallet/ledger');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'wallet',
                    'entries' => [['id', 'type', 'type_label', 'amount', 'running_balance']],
                ],
            ]);
    }

    /** @test */
    public function member_cannot_view_ledger()
    {
        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/wallet/ledger');

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // PUT /wallet/threshold
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_set_threshold()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/wallet/threshold', ['threshold' => 200]);

        $response->assertOk()
            ->assertJsonPath('data.low_balance_threshold', '200.00');
    }

    /** @test */
    public function member_cannot_set_threshold()
    {
        $response = $this->actingAs($this->member)
            ->putJson('/api/v1/wallet/threshold', ['threshold' => 100]);

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // Billing: Payment Methods
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_list_payment_methods()
    {
        PaymentMethod::factory()->count(2)->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/billing/methods');

        $response->assertOk()
            ->assertJsonPath('meta.count', 2);
    }

    /** @test */
    public function member_cannot_list_payment_methods()
    {
        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/billing/methods');

        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_add_payment_method()
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/billing/methods', [
                'provider'        => 'visa',
                'last_four'       => '4242',
                'expiry_month'    => '12',
                'expiry_year'     => '2028',
                'cardholder_name' => 'Ahmed Test',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.provider', 'visa')
            ->assertJsonPath('data.last_four', '4242')
            ->assertJsonPath('data.is_masked', false);
    }

    /** @test */
    public function owner_can_remove_payment_method()
    {
        $method = PaymentMethod::factory()->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/billing/methods/{$method->id}");

        $response->assertOk();
        $this->assertSoftDeleted('payment_methods', ['id' => $method->id]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-020: Disabled Account Masking
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function suspended_account_shows_masked_cards()
    {
        $this->account->update(['status' => 'suspended']);
        PaymentMethod::factory()->create([
            'account_id' => $this->account->id,
            'provider'   => 'visa',
            'last_four'  => '9999',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/billing/methods');

        $response->assertOk();
        $methods = $response->json('data');
        $this->assertTrue($methods[0]['is_masked']);
        $this->assertEquals('••••', $methods[0]['last_four']);
        $this->assertEquals('account_disabled', $methods[0]['mask_reason']);
    }

    /** @test */
    public function cannot_add_card_to_suspended_account()
    {
        $this->account->update(['status' => 'suspended']);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/billing/methods', [
                'provider'  => 'visa',
                'last_four' => '1234',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error_code', 'ERR_ACCOUNT_DISABLED');
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /wallet/permissions
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function permissions_endpoint_returns_list()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/wallet/permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'wallet:balance', 'wallet:ledger', 'wallet:topup',
                    'wallet:configure', 'billing:view', 'billing:manage',
                ],
            ]);
    }
}
