<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC Module — KYC Compliance & Verification
 * FR-KYC-001→008 (8 requirements)
 *
 * Tables:
 *   1. verification_cases     — FR-KYC-001/003: Case per account/org with status flow
 *   2. verification_documents — FR-KYC-002/007: Uploaded documents (PII, encrypted)
 *   3. verification_reviews   — FR-KYC-005: Reviewer decisions (accept/reject + reason)
 *   4. verification_restrictions — FR-KYC-004: Restrictions applied to unverified accounts
 *   5. kyc_audit_logs         — FR-KYC-008: Dedicated audit trail for KYC operations
 */
return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 1. verification_cases — FR-KYC-001/003
        // ═══════════════════════════════════════════════════════════
        Schema::create('verification_cases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();

            $table->string('case_number', 20)->unique();
            $table->enum('account_type', ['individual', 'organization'])->default('individual');
            $table->enum('status', [
                'unverified',       // FR-KYC-001: Default
                'pending_review',   // After document submission
                'under_review',     // Reviewer opened
                'verified',         // Approved
                'rejected',         // Rejected with reason
                'expired',          // Verification expired (future)
            ])->default('unverified');

            // ── Applicant Info ───────────────────────────────
            $table->string('applicant_name', 300)->nullable();
            $table->string('applicant_email', 200)->nullable();
            $table->string('applicant_phone', 20)->nullable();
            $table->string('country_code', 2)->default('SA');

            // ── Decision ─────────────────────────────────────
            $table->text('rejection_reason')->nullable();
            $table->string('reviewed_by', 100)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('submission_count')->default(0);
            $table->json('required_documents')->nullable(); // List of doc types needed

            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        // ═══════════════════════════════════════════════════════════
        // 2. verification_documents — FR-KYC-002/007
        // ═══════════════════════════════════════════════════════════
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('verification_cases')->cascadeOnDelete();

            $table->enum('document_type', [
                'national_id',          // هوية وطنية
                'passport',             // جواز سفر
                'commercial_register',  // سجل تجاري
                'tax_certificate',      // بطاقة ضريبية
                'bank_statement',       // كشف حساب بنكي
                'utility_bill',         // فاتورة خدمات
                'other',
            ]);

            $table->string('original_filename', 500);
            $table->string('stored_path', 1000);      // Encrypted storage path
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');   // bytes
            $table->string('file_hash', 128);          // SHA-256 integrity check

            $table->enum('status', ['uploaded', 'accepted', 'rejected'])->default('uploaded');
            $table->text('rejection_note')->nullable();

            // ── Security (FR-KYC-007) ────────────────────────
            $table->boolean('is_encrypted')->default(true);
            $table->string('encryption_key_id', 100)->nullable();

            $table->timestamp('uploaded_at');
            $table->string('uploaded_by', 100);
            $table->timestamps();

            $table->index(['case_id', 'document_type']);
        });

        // ═══════════════════════════════════════════════════════════
        // 3. verification_reviews — FR-KYC-005
        // ═══════════════════════════════════════════════════════════
        Schema::create('verification_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->constrained('verification_cases')->cascadeOnDelete();
            $table->foreignUuid('reviewer_id')->constrained('users');

            $table->enum('decision', ['approved', 'rejected', 'needs_more_info']);
            $table->text('reason')->nullable();
            $table->text('internal_notes')->nullable();

            $table->json('document_decisions')->nullable(); // Per-document accept/reject

            $table->timestamps();

            $table->index(['case_id', 'created_at']);
        });

        // ═══════════════════════════════════════════════════════════
        // 4. verification_restrictions — FR-KYC-004
        // ═══════════════════════════════════════════════════════════
        Schema::create('verification_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 200);
            $table->string('restriction_key', 100)->unique(); // e.g. intl_shipping, max_shipments
            $table->text('description')->nullable();

            // What statuses this restriction applies to
            $table->json('applies_to_statuses');  // ['unverified', 'rejected']

            $table->enum('restriction_type', [
                'block_feature',    // Completely block a feature
                'quota_limit',      // Limit quantity
            ]);
            $table->integer('quota_value')->nullable();       // For quota_limit type
            $table->string('feature_key', 100)->nullable();   // Feature being restricted

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ═══════════════════════════════════════════════════════════
        // 5. kyc_audit_logs — FR-KYC-008
        // ═══════════════════════════════════════════════════════════
        Schema::create('kyc_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('case_id')->nullable()->constrained('verification_cases')->nullOnDelete();
            $table->foreignUuid('document_id')->nullable()->constrained('verification_documents')->nullOnDelete();

            $table->string('actor_id', 100);
            $table->string('actor_type', 50);        // user, admin, system
            $table->string('action', 100);            // upload, view, download, decision, status_change
            $table->string('ip_address', 45)->nullable();

            $table->json('metadata')->nullable();     // Extra context (no document content)
            $table->string('result', 50)->default('success'); // success, denied, error

            $table->timestamps();

            $table->index(['case_id', 'created_at']);
            $table->index(['actor_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_audit_logs');
        Schema::dropIfExists('verification_restrictions');
        Schema::dropIfExists('verification_reviews');
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('verification_cases');
    }
};
