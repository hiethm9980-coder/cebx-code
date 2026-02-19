<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ReportExport;
use App\Models\Role;
use App\Models\ScheduledReport;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Tests — RPT Module (FR-RPT-001→010)
 *
 * 18 tests covering all report API endpoints.
 */
class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = Account::factory()->create();
        $role = Role::factory()->create(['account_id' => $this->account->id, 'slug' => 'owner']);
        $this->owner = User::factory()->create(['account_id' => $this->account->id, 'role_id' => $role->id]);
    }

    /** @test FR-RPT-001 */
    public function test_api_shipment_dashboard(): void
    {
        Shipment::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/shipment-dashboard');
        $response->assertOk()->assertJsonPath('data.total_shipments', 3);
    }

    /** @test FR-RPT-001 with filters */
    public function test_api_dashboard_with_filters(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/reports/shipment-dashboard?date_from=2026-01-01&date_to=2026-01-31');
        $response->assertOk();
    }

    /** @test FR-RPT-002 */
    public function test_api_profit_report(): void
    {
        Shipment::factory()->count(2)->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/profit');
        $response->assertOk()
            ->assertJsonStructure(['data' => ['shipments', 'totals']]);
    }

    /** @test FR-RPT-003: Create export */
    public function test_api_create_export(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/reports/export', [
                'report_type' => 'shipment_summary',
                'format'      => 'csv',
            ]);
        $response->assertStatus(201)->assertJsonPath('data.format', 'csv');
    }

    /** @test FR-RPT-003: List exports */
    public function test_api_list_exports(): void
    {
        ReportExport::factory()->count(2)->create([
            'account_id' => $this->account->id, 'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/exports');
        $response->assertOk()->assertJsonPath('data.total', 2);
    }

    /** @test FR-RPT-004 */
    public function test_api_exception_report(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/exceptions');
        $response->assertOk()->assertJsonStructure(['data' => ['total_exceptions', 'exception_rate']]);
    }

    /** @test FR-RPT-005: Operational */
    public function test_api_operational_report(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/operational');
        $response->assertOk()->assertJsonStructure(['data' => ['shipments', 'exceptions']]);
    }

    /** @test FR-RPT-005: Financial */
    public function test_api_financial_report(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/financial');
        $response->assertOk()->assertJsonStructure(['data' => ['profit_loss', 'wallet']]);
    }

    /** @test FR-RPT-006 */
    public function test_api_grouped_data(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/grouped?group_by=month');
        $response->assertOk();
    }

    /** @test FR-RPT-007: Carrier performance */
    public function test_api_carrier_performance(): void
    {
        Shipment::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/carrier-performance');
        $response->assertOk();
    }

    /** @test FR-RPT-007: Store performance */
    public function test_api_store_performance(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/store-performance');
        $response->assertOk();
    }

    /** @test FR-RPT-007: Revenue */
    public function test_api_revenue_chart(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/revenue?group_by=month');
        $response->assertOk();
    }

    /** @test FR-RPT-008: Create schedule */
    public function test_api_create_schedule(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/reports/schedules', [
                'name'        => 'Weekly Summary',
                'report_type' => 'shipment_summary',
                'frequency'   => 'weekly',
                'recipients'  => ['admin@test.com'],
            ]);
        $response->assertStatus(201);
    }

    /** @test FR-RPT-008: List schedules */
    public function test_api_list_schedules(): void
    {
        ScheduledReport::factory()->create([
            'account_id' => $this->account->id, 'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/schedules');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    /** @test FR-RPT-008: Cancel schedule */
    public function test_api_cancel_schedule(): void
    {
        $schedule = ScheduledReport::factory()->create([
            'account_id' => $this->account->id, 'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/reports/schedules/{$schedule->id}");
        $response->assertOk();
    }

    /** @test FR-RPT-009 */
    public function test_api_wallet_report(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/wallet');
        $response->assertOk()->assertJsonStructure(['data' => ['total_deposits', 'total_charges']]);
    }

    /** @test FR-RPT-010 */
    public function test_api_generic_report(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/api/shipment_summary');
        $response->assertOk();
    }

    /** @test Saved reports */
    public function test_api_save_and_list_reports(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/reports/saved', [
            'name' => 'My Report', 'report_type' => 'shipment_summary',
        ])->assertStatus(201);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/reports/saved');
        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
