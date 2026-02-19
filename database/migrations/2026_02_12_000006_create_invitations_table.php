<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-IAM-011: آلية الدعوات للمستخدمين
 *
 * Creates the invitations table to support:
 * - Invite users via email with a unique secure token
 * - Assign a role upon acceptance
 * - TTL-based expiration (configurable, default 72h)
 * - Status lifecycle: pending → accepted | expired | cancelled
 * - Resend capability (only when pending)
 * - Tenant-scoped (account_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('email');
            $table->string('name')->nullable();          // Optional: pre-set display name
            $table->uuid('role_id')->nullable();          // Role to assign upon acceptance
            $table->string('token', 128)->unique();       // Secure unique invitation token
            $table->enum('status', ['pending', 'accepted', 'expired', 'cancelled'])
                  ->default('pending')
                  ->index();
            $table->uuid('invited_by');                   // User who created the invitation
            $table->uuid('accepted_by')->nullable();      // User who accepted (once created)
            $table->timestamp('expires_at');              // TTL expiration timestamp
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_sent_at')->nullable(); // Last email send timestamp
            $table->unsignedInteger('send_count')->default(1); // How many times sent/resent
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('accepted_by')->references('id')->on('users')->onDelete('set null');

            // Note: uniqueness of pending invitation per email+account enforced in InvitationService
            // We allow multiple rows (cancelled/expired) but only one pending at a time
            $table->index(['account_id', 'email']);

            // Indexes for common queries
            $table->index(['account_id', 'status']);
            $table->index('token');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
