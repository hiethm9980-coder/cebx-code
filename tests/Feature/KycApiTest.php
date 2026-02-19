<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\User;
use App\Models\Role;
use App\Models\KycVerification;
use App\Models\KycDocument;
use App\Models\AuditLog;
use App\Services\AuditService;

/**
 * FR-IAM-014 + FR-IAM-016: KYC Status & Document Access — Integration Tests (22 tests)
 */
class KycApiTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $owner;
    protected User $member;
    protected User $kycManager;

    protected function setUp(): void
    {
        parent::setUp();
        AuditService::resetRequestId();

        $this->account = Account::factory()->create(['kyc_status' => 'unverified']);
        $this->owner = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => true,
        ]);
        $this->member = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
        ]);

        $kycRole = Role::factory()->create([
            'account_id'  => $this->account->id,
            'permissions' => ['kyc:manage', 'kyc:documents', 'kyc:view'],
        ]);
        $this->kycManager = User::factory()->create([
            'account_id' => $this->account->id,
            'is_owner'   => false,
            'role_id'    => $kycRole->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-014: KYC Status Endpoint
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_view_kyc_status_unverified()
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/kyc/status');

        $response->assertOk()
            ->assertJsonPath('data.status', 'unverified')
            ->assertJsonStructure([
                'data' => [
                    'status', 'status_display', 'verification_type',
                    'capabilities', 'required_documents',
                ],
            ]);

        $this->assertFalse($response->json('data.capabilities.can_ship_international'));
    }

    /** @test */
    public function status_shows_pending_with_capabilities()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/kyc/status');

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_display.color', 'yellow')
            ->assertJsonPath('data.capabilities.can_ship_domestic', true)
            ->assertJsonPath('data.capabilities.can_ship_international', false);
    }

    /** @test */
    public function status_shows_approved_with_full_capabilities()
    {
        KycVerification::factory()->approved()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/kyc/status');

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.status_display.color', 'green')
            ->assertJsonPath('data.capabilities.can_ship_international', true)
            ->assertJsonPath('data.capabilities.can_use_cod', true);

        $this->assertNull($response->json('data.capabilities.shipping_limit'));
    }

    /** @test */
    public function status_shows_rejected_with_reason()
    {
        KycVerification::factory()->rejected()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/kyc/status');

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.status_display.color', 'red');

        $this->assertNotNull($response->json('data.rejection_reason'));
    }

    /** @test */
    public function status_includes_document_count()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        KycDocument::factory()->count(2)->create([
            'account_id'          => $this->account->id,
            'kyc_verification_id' => $kyc->id,
            'uploaded_by'         => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/kyc/status');

        $response->assertOk()
            ->assertJsonPath('data.documents_count', 2);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-014: Approve / Reject / Resubmit
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_approve_kyc()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/kyc/approve', [
                'account_id' => $this->account->id,
                'notes'      => 'All documents verified',
                'level'      => 'enhanced',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertEquals('approved', $this->account->fresh()->kyc_status);
    }

    /** @test */
    public function kyc_manager_can_approve_kyc()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->kycManager)
            ->postJson('/api/v1/kyc/approve', [
                'account_id' => $this->account->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    /** @test */
    public function member_without_permission_cannot_approve()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->member)
            ->postJson('/api/v1/kyc/approve', [
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_reject_kyc_with_reason()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/kyc/reject', [
                'account_id' => $this->account->id,
                'reason'     => 'صور غير واضحة',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'صور غير واضحة');
    }

    /** @test */
    public function reject_requires_reason()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/kyc/reject', [
                'account_id' => $this->account->id,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function can_resubmit_after_rejection()
    {
        KycVerification::factory()->rejected()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/kyc/resubmit', [
                'documents' => ['national_id' => 'new_upload.pdf'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending');
    }

    /** @test */
    public function cannot_resubmit_when_pending()
    {
        KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/kyc/resubmit', [
                'documents' => ['doc' => 'path'],
            ]);

        $response->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════
    // FR-IAM-016: Document Access Control
    // ═══════════════════════════════════════════════════════════════

    /** @test */
    public function owner_can_list_kyc_documents()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        KycDocument::factory()->count(3)->create([
            'account_id'          => $this->account->id,
            'kyc_verification_id' => $kyc->id,
            'uploaded_by'         => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/kyc/documents');

        $response->assertOk()
            ->assertJsonPath('meta.count', 3)
            ->assertJsonStructure([
                'data' => [['id', 'document_type', 'original_filename', 'is_sensitive']],
            ]);
    }

    /** @test */
    public function member_without_documents_permission_cannot_list()
    {
        $response = $this->actingAs($this->member)
            ->getJson('/api/v1/kyc/documents');

        $response->assertStatus(403);
    }

    /** @test */
    public function document_access_denied_is_audit_logged()
    {
        $this->actingAs($this->member)
            ->getJson('/api/v1/kyc/documents');

        $log = AuditLog::withoutGlobalScopes()
            ->where('action', 'kyc.document_access_denied')
            ->first();

        $this->assertNotNull($log);
    }

    /** @test */
    public function owner_can_upload_document()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/kyc/documents/upload', [
                'kyc_verification_id' => $kyc->id,
                'document_type'       => 'national_id',
                'filename'            => 'id_card.pdf',
                'stored_path'         => 'kyc/uploads/id.pdf',
                'mime_type'           => 'application/pdf',
                'file_size'           => 102400,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.document_type', 'national_id');
    }

    /** @test */
    public function owner_can_download_document()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);
        $doc = KycDocument::factory()->create([
            'account_id'          => $this->account->id,
            'kyc_verification_id' => $kyc->id,
            'uploaded_by'         => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/kyc/documents/{$doc->id}/download");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['download_url', 'expires_at', 'ttl_minutes'],
            ])
            ->assertJsonPath('data.ttl_minutes', 15);
    }

    /** @test */
    public function download_is_audit_logged()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);
        $doc = KycDocument::factory()->create([
            'account_id'          => $this->account->id,
            'kyc_verification_id' => $kyc->id,
            'uploaded_by'         => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/kyc/documents/{$doc->id}/download");

        $log = AuditLog::withoutGlobalScopes()
            ->where('action', 'kyc.document_accessed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($doc->id, $log->entity_id);
    }

    /** @test */
    public function owner_can_purge_document()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);
        $doc = KycDocument::factory()->create([
            'account_id'          => $this->account->id,
            'kyc_verification_id' => $kyc->id,
            'uploaded_by'         => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/v1/kyc/documents/{$doc->id}");

        $response->assertOk()
            ->assertJsonPath('data.is_purged', true);

        $this->assertTrue($doc->fresh()->is_purged);
    }

    /** @test */
    public function member_without_manage_cannot_purge()
    {
        $kyc = KycVerification::factory()->pending()->create([
            'account_id' => $this->account->id,
        ]);
        $doc = KycDocument::factory()->create([
            'account_id'          => $this->account->id,
            'kyc_verification_id' => $kyc->id,
            'uploaded_by'         => $this->owner->id,
        ]);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/v1/kyc/documents/{$doc->id}");

        $response->assertStatus(403);
    }
}
