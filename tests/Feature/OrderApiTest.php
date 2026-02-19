<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\AuditLog;
use App\Services\AuditService;

/**
 * ST Module Integration Tests — API endpoints (20 tests)
 */
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $owner;
    protected User $manager;
    protected User $member;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        AuditService::resetRequestId();

        $this->account = Account::factory()->create();
        $this->owner = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => true,
        ]);

        $managerRole = Role::factory()->create([
            'account_id'  => $this->account->id,
            'permissions' => ['orders:manage', 'store:manage'],
        ]);
        $this->manager = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
            'role_id'    => $managerRole->id,
        ]);

        $this->member = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
        ]);

        $this->store = Store::factory()->create([
            'account_id' => $this->account->id,
            'platform'   => 'manual',
            'status'     => 'active',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /orders (Manual Creation)
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_create_order()
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/orders', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonPath('data.source', 'manual')
            ->assertJsonPath('data.customer_name', 'Ahmed Ali');
    }

    /** @test */
    public function manager_can_create_order()
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/orders', $this->validPayload());

        $response->assertStatus(201);
    }

    /** @test */
    public function member_cannot_create_order()
    {
        $response = $this->actingAs($this->member)
            ->postJson('/api/v1/orders', $this->validPayload());

        $response->assertStatus(403);
    }

    /** @test */
    public function create_order_validates_required_fields()
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/orders', ['store_id' => $this->store->id]);

        $response->assertStatus(422);
    }

    /** @test */
    public function create_order_requires_items()
    {
        $payload = $this->validPayload();
        unset($payload['items']);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/orders', $payload);

        $response->assertStatus(422);
    }

    /** @test */
    public function order_creation_is_audit_logged()
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/orders', $this->validPayload());

        $log = AuditLog::withoutGlobalScopes()->where('action', 'order.created')->first();
        $this->assertNotNull($log);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /orders
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_list_orders()
    {
        Order::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    /** @test */
    public function list_filters_by_status()
    {
        Order::factory()->ready()->count(2)->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);
        Order::factory()->shipped()->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/orders?status=ready');

        $response->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    /** @test */
    public function list_filters_by_store()
    {
        $store2 = Store::factory()->create(['account_id' => $this->account->id]);
        Order::factory()->create(['account_id' => $this->account->id, 'store_id' => $this->store->id]);
        Order::factory()->create(['account_id' => $this->account->id, 'store_id' => $store2->id]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/orders?store_id={$this->store->id}");

        $response->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /orders/{id}
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_view_single_order()
    {
        $order = Order::factory()->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    // ═══════════════════════════════════════════════════════════════
    // PUT /orders/{id}/status
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_update_status()
    {
        $order = Order::factory()->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
            'status'     => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'ready']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'ready');
    }

    /** @test */
    public function invalid_status_transition_rejected()
    {
        $order = Order::factory()->shipped()->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/v1/orders/{$order->id}/status", ['status' => 'pending']);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /orders/{id}/cancel
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_cancel_order()
    {
        $order = Order::factory()->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/orders/{$order->id}/cancel", ['reason' => 'Changed mind']);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function cannot_cancel_shipped_order()
    {
        $order = Order::factory()->shipped()->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /orders/stats
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_get_order_stats()
    {
        Order::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'store_id'   => $this->store->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/orders/stats');

        $response->assertOk()
            ->assertJsonPath('data.total', 3);
    }

    // ═══════════════════════════════════════════════════════════════
    // Store Connection & Sync
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_test_connection()
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/stores/{$this->store->id}/test-connection");

        $response->assertOk()
            ->assertJsonPath('data.success', true);
    }

    /** @test */
    public function owner_can_register_webhooks()
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/stores/{$this->store->id}/register-webhooks");

        $response->assertOk();
    }

    /** @test */
    public function manual_store_sync_not_supported()
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/stores/{$this->store->id}/sync");

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════
    // Webhooks (Public Endpoint)
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function webhook_returns_404_for_unknown_store()
    {
        $fakeId = \Illuminate\Support\Str::uuid();
        $response = $this->postJson("/api/v1/webhooks/shopify/{$fakeId}", ['id' => '123']);

        $response->assertStatus(404);
    }

    // ═══════════════════════════════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════════════════════════════

    private function validPayload(): array
    {
        return [
            'store_id'                => $this->store->id,
            'customer_name'           => 'Ahmed Ali',
            'customer_email'          => 'ahmed@test.com',
            'customer_phone'          => '+966501234567',
            'shipping_address_line_1' => '123 Main St',
            'shipping_city'           => 'Riyadh',
            'shipping_country'        => 'SA',
            'items' => [
                ['name' => 'Test Product', 'quantity' => 1, 'unit_price' => 100],
            ],
        ];
    }
}
