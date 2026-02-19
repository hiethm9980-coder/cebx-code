<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\OrganizationMember;
use App\Models\OrganizationWallet;
use App\Models\PermissionCatalog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Tests — ORG Module (FR-ORG-001→010)
 *
 * 20 tests covering all organization API endpoints.
 */
class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $owner;
    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->account = Account::factory()->create();
        $role = Role::factory()->create(['account_id' => $this->account->id, 'slug' => 'owner']);
        $this->owner = User::factory()->create(['account_id' => $this->account->id, 'role_id' => $role->id]);
    }

    private function createOrg(): Organization
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/organizations', [
            'legal_name' => 'Test Company', 'country_code' => 'SA',
        ]);
        return Organization::find($response->json('data.id'));
    }

    /** @test FR-ORG-001 */
    public function test_api_create_organization(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/organizations', [
            'legal_name' => 'شركة اختبار', 'country_code' => 'SA',
        ]);
        $response->assertStatus(201)->assertJsonPath('data.legal_name', 'شركة اختبار');
    }

    /** @test FR-ORG-001 */
    public function test_api_list_organizations(): void
    {
        $this->createOrg();
        $response = $this->actingAs($this->owner)->getJson('/api/v1/organizations');
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    /** @test FR-ORG-002 */
    public function test_api_get_organization(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->getJson("/api/v1/organizations/{$org->id}");
        $response->assertOk()->assertJsonPath('data.id', $org->id);
    }

    /** @test FR-ORG-002 */
    public function test_api_update_profile(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->putJson("/api/v1/organizations/{$org->id}", [
            'legal_name' => 'Updated LLC',
        ]);
        $response->assertOk()->assertJsonPath('data.legal_name', 'Updated LLC');
    }

    /** @test FR-ORG-003 */
    public function test_api_invite_member(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->postJson("/api/v1/organizations/{$org->id}/invites", [
            'email' => 'member@test.com',
        ]);
        $response->assertStatus(201)->assertJsonPath('data.status', 'pending');
    }

    /** @test FR-ORG-003 */
    public function test_api_list_invites(): void
    {
        $org = $this->createOrg();
        OrganizationInvite::factory()->create(['organization_id' => $org->id, 'invited_by' => $this->owner->id]);

        $response = $this->actingAs($this->owner)->getJson("/api/v1/organizations/{$org->id}/invites");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    /** @test FR-ORG-003 */
    public function test_api_accept_invite(): void
    {
        $org = $this->createOrg();
        $invite = OrganizationInvite::factory()->create(['organization_id' => $org->id, 'invited_by' => $this->owner->id]);

        $newUser = User::factory()->create(['account_id' => $this->account->id, 'role_id' => Role::factory()->create(['account_id' => $this->account->id])->id]);
        $response = $this->actingAs($newUser)->postJson('/api/v1/organizations/invites/accept', ['token' => $invite->token]);
        $response->assertOk()->assertJsonPath('data.status', 'active');
    }

    /** @test FR-ORG-003 */
    public function test_api_cancel_invite(): void
    {
        $org = $this->createOrg();
        $invite = OrganizationInvite::factory()->create(['organization_id' => $org->id, 'invited_by' => $this->owner->id]);

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/organizations/invites/{$invite->id}");
        $response->assertOk();
    }

    /** @test FR-ORG-004 */
    public function test_api_permission_catalog(): void
    {
        PermissionCatalog::create(['key' => 'test.perm', 'name' => 'Test', 'module' => 'SH', 'category' => 'operational']);
        $response = $this->actingAs($this->owner)->getJson('/api/v1/organizations/permissions/catalog');
        $response->assertOk();
    }

    /** @test FR-ORG-005 */
    public function test_api_set_financial_access(): void
    {
        $org = $this->createOrg();
        $member = OrganizationMember::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($this->owner)->putJson("/api/v1/organizations/members/{$member->id}/financial-access", [
            'can_view_financial' => true,
        ]);
        $response->assertOk()->assertJsonPath('data.can_view_financial', true);
    }

    /** @test FR-ORG-006 */
    public function test_api_check_permission(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->postJson("/api/v1/organizations/{$org->id}/check-permission", [
            'permission' => 'any.perm',
        ]);
        $response->assertOk()->assertJsonPath('data.allowed', true);
    }

    /** @test FR-ORG-007 */
    public function test_api_list_members(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->getJson("/api/v1/organizations/{$org->id}/members");
        $response->assertOk()->assertJsonCount(1, 'data');
    }

    /** @test FR-ORG-007 */
    public function test_api_transfer_ownership(): void
    {
        $org = $this->createOrg();
        $newOwner = User::factory()->create(['account_id' => $this->account->id, 'role_id' => Role::factory()->create(['account_id' => $this->account->id])->id]);
        OrganizationMember::factory()->create(['organization_id' => $org->id, 'user_id' => $newOwner->id]);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/organizations/{$org->id}/transfer-ownership", [
            'new_owner_id' => $newOwner->id,
        ]);
        $response->assertOk();
    }

    /** @test FR-ORG-007 */
    public function test_api_suspend_member(): void
    {
        $org = $this->createOrg();
        $member = OrganizationMember::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/organizations/members/{$member->id}/suspend", [
            'reason' => 'TOS violation',
        ]);
        $response->assertOk()->assertJsonPath('data.status', 'suspended');
    }

    /** @test FR-ORG-007 */
    public function test_api_remove_member(): void
    {
        $org = $this->createOrg();
        $member = OrganizationMember::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($this->owner)->deleteJson("/api/v1/organizations/members/{$member->id}");
        $response->assertOk();
    }

    /** @test FR-ORG-008 */
    public function test_api_submit_verification(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->postJson("/api/v1/organizations/{$org->id}/submit-verification");
        $response->assertOk()->assertJsonPath('data.verification_status', 'pending_review');
    }

    /** @test FR-ORG-009 */
    public function test_api_wallet_summary(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->getJson("/api/v1/organizations/{$org->id}/wallet");
        $response->assertOk()->assertJsonStructure(['data' => ['balance', 'available_balance', 'currency']]);
    }

    /** @test FR-ORG-009 */
    public function test_api_topup_wallet(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->postJson("/api/v1/organizations/{$org->id}/wallet/topup", ['amount' => 500]);
        $response->assertOk()->assertJsonPath('data.balance', '500.00');
    }

    /** @test FR-ORG-010 */
    public function test_api_update_wallet_settings(): void
    {
        $org = $this->createOrg();
        $response = $this->actingAs($this->owner)->putJson("/api/v1/organizations/{$org->id}/wallet/settings", [
            'low_balance_threshold' => 250, 'auto_topup_enabled' => true,
        ]);
        $response->assertOk()->assertJsonPath('data.auto_topup_enabled', true);
    }

    /** @test FR-ORG-007 */
    public function test_api_update_member_role(): void
    {
        $org = $this->createOrg();
        $member = OrganizationMember::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($this->owner)->putJson("/api/v1/organizations/members/{$member->id}/role", [
            'membership_role' => 'admin',
        ]);
        $response->assertOk()->assertJsonPath('data.membership_role', 'admin');
    }
}
