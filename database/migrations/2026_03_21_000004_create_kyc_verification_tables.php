<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the KYC / identity-verification tables that have no prior migration.
 *
 * Models that depend on these tables:
 *   - VerificationCase        (verification_cases)
 *   - VerificationRestriction (verification_restrictions)
 *   - VerificationDocument    (verification_documents)
 *   - VerificationReview      (verification_reviews)
 *   - KycAuditLog             (kyc_audit_logs)
 *
 * All PKs are UUID to match the rest of the schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── verification_cases ───────────────────────────────────────────
        if (! Schema::hasTable('verification_cases')) {
            Schema::create('verification_cases', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('account_id')->index();
                $table->uuid('organization_id')->nullable()->index();
                $table->string('case_number')->unique();
                $table->string('account_type')->nullable();
                $table->string('status')->default('unverified');
                $table->string('applicant_name')->nullable();
                $table->string('applicant_email')->nullable();
                $table->string('applicant_phone')->nullable();
                $table->string('country_code', 5)->nullable();
                $table->text('rejection_reason')->nullable();
                $table->uuid('reviewed_by')->nullable()->index();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedInteger('submission_count')->default(0);
                $table->json('required_documents')->nullable();
                $table->timestamps();

                $table->foreign('account_id')
                      ->references('id')->on('accounts')
                      ->cascadeOnDelete();
            });
        }

        // ── verification_restrictions ────────────────────────────────────
        if (! Schema::hasTable('verification_restrictions')) {
            Schema::create('verification_restrictions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('restriction_key')->unique();
                $table->text('description')->nullable();
                $table->json('applies_to_statuses')->nullable();
                $table->string('restriction_type')->default('block_feature'); // block_feature | quota_limit
                $table->unsignedInteger('quota_value')->nullable();
                $table->string('feature_key')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── verification_documents ───────────────────────────────────────
        if (! Schema::hasTable('verification_documents')) {
            Schema::create('verification_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('case_id')->index();
                $table->string('document_type');
                $table->string('original_filename')->nullable();
                $table->string('stored_path')->nullable();
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('file_hash', 64)->nullable();
                $table->string('status')->default('pending'); // pending | accepted | rejected
                $table->text('rejection_note')->nullable();
                $table->boolean('is_encrypted')->default(false);
                $table->string('encryption_key_id')->nullable();
                $table->timestamp('uploaded_at')->nullable();
                $table->uuid('uploaded_by')->nullable()->index();
                $table->timestamps();

                $table->foreign('case_id')
                      ->references('id')->on('verification_cases')
                      ->cascadeOnDelete();
            });
        }

        // ── verification_reviews ─────────────────────────────────────────
        if (! Schema::hasTable('verification_reviews')) {
            Schema::create('verification_reviews', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('case_id')->index();
                $table->uuid('reviewer_id')->nullable()->index();
                $table->string('decision'); // approved | rejected | needs_more_info
                $table->text('reason')->nullable();
                $table->text('internal_notes')->nullable();
                $table->json('document_decisions')->nullable();
                $table->timestamps();

                $table->foreign('case_id')
                      ->references('id')->on('verification_cases')
                      ->cascadeOnDelete();
            });
        }

        // ── kyc_audit_logs ───────────────────────────────────────────────
        if (! Schema::hasTable('kyc_audit_logs')) {
            Schema::create('kyc_audit_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('case_id')->nullable()->index();
                $table->uuid('document_id')->nullable()->index();
                $table->uuid('actor_id')->index();
                $table->string('actor_type')->default('user');
                $table->string('action');
                $table->string('ip_address', 45)->nullable();
                $table->json('metadata')->nullable();
                $table->string('result')->default('success');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_audit_logs');
        Schema::dropIfExists('verification_reviews');
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('verification_cases');
        Schema::dropIfExists('verification_restrictions');
    }
};
