<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\User;
use App\Models\Role;
use App\Models\AuditLog;
use App\Services\AuditService;

/**
 * FR-IAM-008: Account Settings — Integration Tests (18 tests)
 */
class AccountSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $owner;
    protected User $admin;
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

        $adminRole = Role::factory()->create([
            'account_id'  => $this->account->id,
            'permissions' => ['account:manage', 'account:view'],
        ]);
        $this->admin = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
            'role_id'    => $adminRole->id,
        ]);

        $this->member = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /account/settings
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_get_settings()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/account/settings');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'language', 'currency', 'timezone', 'country',
                    'contact_phone', 'contact_email',
                    'address' => ['line_1', 'line_2', 'city', 'postal_code', 'country'],
                    'date_format', 'weight_unit', 'dimension_unit', 'extended',
                ],
            ])
            ->assertJsonPath('data.language', 'ar')
            ->assertJsonPath('data.currency', 'SAR');
    }

    /** @test */
    public function member_can_view_settings()
    {
        // Any authenticated user can VIEW settings
        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/account/settings');

        $response->assertOk();
    }

    // ═══════════════════════════════════════════════════════════════
    // PUT /account/settings
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_update_language_and_currency()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'language' => 'en',
                'currency' => 'USD',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.currency', 'USD');

        $this->assertEquals('en', $this->account->fresh()->language);
        $this->assertEquals('USD', $this->account->fresh()->currency);
    }

    /** @test */
    public function owner_can_update_timezone()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'timezone' => 'Europe/London',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.timezone', 'Europe/London');
    }

    /** @test */
    public function owner_can_update_address_and_contact()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'contact_phone'  => '+966501234567',
                'contact_email'  => 'info@test.sa',
                'address_line_1' => 'شارع الملك فهد',
                'city'           => 'الرياض',
                'postal_code'    => '12345',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.contact_phone', '+966501234567')
            ->assertJsonPath('data.address.line_1', 'شارع الملك فهد')
            ->assertJsonPath('data.address.city', 'الرياض');
    }

    /** @test */
    public function owner_can_update_units()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'weight_unit'    => 'lb',
                'dimension_unit' => 'in',
                'date_format'    => 'd/m/Y',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.weight_unit', 'lb')
            ->assertJsonPath('data.dimension_unit', 'in')
            ->assertJsonPath('data.date_format', 'd/m/Y');
    }

    /** @test */
    public function admin_can_update_settings()
    {
        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/account/settings', [
                'language' => 'en',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.language', 'en');
    }

    /** @test */
    public function member_without_permission_cannot_update()
    {
        $response = $this->actingAs($this->member)
            ->putJson('/api/v1/account/settings', [
                'language' => 'en',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function unsupported_currency_is_rejected()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'currency' => 'XYZ',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    /** @test */
    public function unsupported_language_is_rejected()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'language' => 'zz',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function unsupported_timezone_is_rejected()
    {
        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'timezone' => 'Invalid/Zone',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function update_creates_audit_log()
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', [
                'language' => 'en',
            ]);

        $log = AuditLog::withoutGlobalScopes()
            ->where('action', 'account.settings_updated')
            ->where('account_id', $this->account->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('ar', $log->old_values['language']);
        $this->assertEquals('en', $log->new_values['language']);
    }

    /** @test */
    public function partial_update_only_changes_specified_fields()
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/account/settings', ['language' => 'en']);

        $account = $this->account->fresh();
        $this->assertEquals('en', $account->language);
        $this->assertEquals('SAR', $account->currency); // Unchanged
        $this->assertEquals('Asia/Riyadh', $account->timezone); // Unchanged
    }

    // ═══════════════════════════════════════════════════════════════
    // POST /account/settings/reset
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_reset_settings()
    {
        // Change first
        $this->account->update(['language' => 'en', 'currency' => 'USD']);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/account/settings/reset');

        $response->assertOk()
            ->assertJsonPath('data.language', 'ar')
            ->assertJsonPath('data.currency', 'SAR')
            ->assertJsonPath('data.timezone', 'Asia/Riyadh');
    }

    /** @test */
    public function member_cannot_reset_settings()
    {
        $response = $this->actingAs($this->member)
            ->postJson('/api/v1/account/settings/reset');

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // GET /account/settings/options
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_get_supported_options()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/account/settings/options');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'languages', 'currencies', 'timezones',
                    'countries', 'date_formats', 'weight_units', 'dimension_units',
                ],
            ]);

        // Verify languages have proper structure
        $langs = $response->json('data.languages');
        $this->assertNotEmpty($langs);
        $this->assertArrayHasKey('code', $langs[0]);
        $this->assertArrayHasKey('name', $langs[0]);

        // Verify currencies have symbols
        $currencies = $response->json('data.currencies');
        $sar = collect($currencies)->firstWhere('code', 'SAR');
        $this->assertNotNull($sar);
        $this->assertArrayHasKey('symbol', $sar);
    }

    /** @test */
    public function options_endpoint_accessible_by_any_user()
    {
        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/account/settings/options');

        $response->assertOk();
    }
}
