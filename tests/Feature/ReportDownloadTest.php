<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Feature tests — Report signed download endpoint.
 *
 * Verifies:
 * 1. Signed URL resolves to the correct path (api/v1/reports/exports/{id}/download)
 * 2. A valid signed URL + completed export returns the file (200)
 * 3. A tampered/unsigned URL is rejected (403)
 * 4. An expired signed URL is rejected (403)
 * 5. A non-completed export returns 404
 */
class ReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $user;
    private ReportExport $export;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->account = Account::factory()->create();
        $this->user    = User::factory()->create(['account_id' => $this->account->id]);

        // Write a fake file to the fake public disk
        $path = "exports/{$this->account->id}/test-export.csv";
        Storage::disk('public')->put($path, "id,name\n1,test\n");

        $this->export = ReportExport::create([
            'account_id'  => $this->account->id,
            'user_id'     => $this->user->id,
            'report_type' => 'shipment_summary',
            'format'      => 'csv',
            'status'      => ReportExport::STATUS_COMPLETED,
            'file_path'   => $path,
            'row_count'   => 1,
            'file_size'   => 14,
        ]);
    }

    // ─── Route path correctness ───────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_signed_url_generates_correct_path(): void
    {
        $url = URL::temporarySignedRoute(
            'api.v1.reports.export-download',
            now()->addDays(7),
            ['exportId' => $this->export->id]
        );

        $this->assertStringContainsString(
            '/api/v1/reports/exports/' . $this->export->id . '/download',
            $url,
            'Signed URL must use correct path without duplicate v1 prefix'
        );
        $this->assertStringNotContainsString(
            'v1/v1',
            $url,
            'Signed URL must not contain duplicate v1 prefix'
        );
        $this->assertStringContainsString('signature=', $url, 'URL must include signature param');
        $this->assertStringContainsString('expires=', $url, 'URL must include expires param');
    }

    // ─── Valid signed URL + completed export ──────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_valid_signed_url_downloads_file(): void
    {
        $url = URL::temporarySignedRoute(
            'api.v1.reports.export-download',
            now()->addDays(7),
            ['exportId' => $this->export->id]
        );

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    // ─── Unsigned request rejected ────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_unsigned_url_is_rejected(): void
    {
        // Hit the endpoint without any signature
        $response = $this->get(
            '/api/v1/reports/exports/' . $this->export->id . '/download'
        );

        $response->assertStatus(403);
    }

    // ─── Tampered signature rejected ─────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_tampered_signature_is_rejected(): void
    {
        $url = URL::temporarySignedRoute(
            'api.v1.reports.export-download',
            now()->addDays(7),
            ['exportId' => $this->export->id]
        );

        // Replace the last 8 chars of the signature with 'TAMPERED'
        $tampered = preg_replace('/signature=([a-f0-9]{56})/', 'signature=000000TAMPERED000000', $url);

        $response = $this->get($tampered);
        $response->assertStatus(403);
    }

    // ─── Expired URL rejected ─────────────────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_expired_signed_url_is_rejected(): void
    {
        // Travel time forward past the expiry
        $url = URL::temporarySignedRoute(
            'api.v1.reports.export-download',
            now()->addSeconds(1),
            ['exportId' => $this->export->id]
        );

        $this->travel(10)->seconds();

        $response = $this->get($url);
        $response->assertStatus(403);
    }

    // ─── Non-completed export returns 404 ────────────────────────

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_pending_export_returns_404(): void
    {
        $pendingExport = ReportExport::create([
            'account_id'  => $this->account->id,
            'user_id'     => $this->user->id,
            'report_type' => 'shipment_summary',
            'format'      => 'csv',
            'status'      => ReportExport::STATUS_PENDING,
        ]);

        $url = URL::temporarySignedRoute(
            'api.v1.reports.export-download',
            now()->addDays(7),
            ['exportId' => $pendingExport->id]
        );

        $response = $this->get($url);
        $response->assertStatus(404);
    }
}
