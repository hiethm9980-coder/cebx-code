<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Exceptions\BusinessException;
use App\Events\InvitationCreated;
use App\Events\InvitationAccepted;
use App\Events\InvitationCancelled;
use App\Events\InvitationResent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationService
{
    /**
     * Default invitation TTL in hours.
     */
    const DEFAULT_TTL_HOURS = 72;

    /**
     * Maximum resend count to prevent spam.
     */
    const MAX_RESEND_COUNT = 5;

    // ─── Create Invitation ───────────────────────────────────────

    /**
     * Create a new invitation for a user to join the account.
     *
     * @throws BusinessException
     */
    public function createInvitation(array $data, User $performer): Invitation
    {
        $this->assertCanInvite($performer);

        $accountId = $performer->account_id;
        $email = strtolower(trim($data['email']));

        // 1. Check if email already belongs to a user in this account
        $existsInAccount = User::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('email', $email)
            ->exists();

        if ($existsInAccount) {
            throw BusinessException::emailAlreadyInAccount();
        }

        // 2. Check if there's already a pending invitation for this email in this account
        $existingPending = Invitation::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('email', $email)
            ->where('status', Invitation::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingPending) {
            throw BusinessException::invitationAlreadyExists();
        }

        // 3. Validate role belongs to this account (if specified)
        if (!empty($data['role_id'])) {
            $this->assertRoleBelongsToAccount($data['role_id'], $accountId);
        }

        // 4. Create the invitation
        return DB::transaction(function () use ($data, $performer, $accountId, $email) {
            $ttlHours = $data['ttl_hours'] ?? self::DEFAULT_TTL_HOURS;

            $invitation = Invitation::withoutGlobalScopes()->create([
                'account_id' => $accountId,
                'email'      => $email,
                'name'       => $data['name'] ?? null,
                'role_id'    => $data['role_id'] ?? null,
                'token'      => $this->generateSecureToken(),
                'status'     => Invitation::STATUS_PENDING,
                'invited_by' => $performer->id,
                'expires_at' => now()->addHours($ttlHours),
                'last_sent_at' => now(),
                'send_count' => 1,
            ]);

            $this->logAction(
                $accountId,
                $performer->id,
                'invitation.created',
                'Invitation',
                $invitation->id,
                null,
                [
                    'email'      => $email,
                    'role_id'    => $invitation->role_id,
                    'expires_at' => $invitation->expires_at->toISOString(),
                ]
            );

            event(new InvitationCreated($invitation, $performer));

            return $invitation;
        });
    }

    // ─── Accept Invitation ───────────────────────────────────────

    /**
     * Accept an invitation using its token.
     * Creates a new user and links them to the account + role.
     *
     * @throws BusinessException
     */
    public function acceptInvitation(string $token, array $userData): array
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->where('token', $token)
            ->first();

        if (!$invitation) {
            throw BusinessException::invitationNotFound();
        }

        // Check status
        if ($invitation->isAccepted()) {
            throw BusinessException::invitationAlreadyAccepted();
        }

        if ($invitation->isCancelled()) {
            throw BusinessException::invitationRevoked();
        }

        if ($invitation->isExpired()) {
            // Auto-expire if still marked pending
            if ($invitation->isPending()) {
                $invitation->update(['status' => Invitation::STATUS_EXPIRED]);
            }
            throw BusinessException::invitationExpired();
        }

        // Check if email is already in the account (someone added them manually)
        $existsInAccount = User::withoutGlobalScopes()
            ->where('account_id', $invitation->account_id)
            ->where('email', $invitation->email)
            ->exists();

        if ($existsInAccount) {
            throw BusinessException::emailAlreadyInAccount();
        }

        return DB::transaction(function () use ($invitation, $userData) {
            // 1. Create the user
            $user = User::withoutGlobalScopes()->create([
                'account_id' => $invitation->account_id,
                'name'       => $userData['name'] ?? $invitation->name ?? 'New User',
                'email'      => $invitation->email,
                'password'   => $userData['password'],
                'phone'      => $userData['phone'] ?? null,
                'status'     => 'active',
                'is_owner'   => false,
                'locale'     => $userData['locale'] ?? 'en',
                'timezone'   => $userData['timezone'] ?? 'UTC',
            ]);

            // 2. Assign role if specified
            if ($invitation->role_id) {
                $role = Role::withoutGlobalScopes()->find($invitation->role_id);
                if ($role) {
                    $user->roles()->attach($role->id, [
                        'assigned_by' => $invitation->invited_by,
                        'assigned_at' => now(),
                    ]);
                }
            }

            // 3. Update invitation status
            $invitation->update([
                'status'      => Invitation::STATUS_ACCEPTED,
                'accepted_by' => $user->id,
                'accepted_at' => now(),
            ]);

            // 4. Audit log
            $this->logAction(
                $invitation->account_id,
                $user->id,
                'invitation.accepted',
                'Invitation',
                $invitation->id,
                ['status' => Invitation::STATUS_PENDING],
                [
                    'status'      => Invitation::STATUS_ACCEPTED,
                    'accepted_by' => $user->id,
                    'user_name'   => $user->name,
                    'role_id'     => $invitation->role_id,
                ]
            );

            event(new InvitationAccepted($invitation, $user));

            return [
                'user'       => $user,
                'invitation' => $invitation->fresh(),
            ];
        });
    }

    // ─── Cancel Invitation ───────────────────────────────────────

    /**
     * Cancel a pending invitation.
     *
     * @throws BusinessException
     */
    public function cancelInvitation(string $invitationId, User $performer): Invitation
    {
        $this->assertCanInvite($performer);

        $invitation = $this->findInvitationOrFail($invitationId, $performer->account_id);

        if (!$invitation->isPending()) {
            throw BusinessException::invitationCannotCancel();
        }

        return DB::transaction(function () use ($invitation, $performer) {
            $invitation->update([
                'status'       => Invitation::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logAction(
                $performer->account_id,
                $performer->id,
                'invitation.cancelled',
                'Invitation',
                $invitation->id,
                ['status' => Invitation::STATUS_PENDING],
                ['status' => Invitation::STATUS_CANCELLED]
            );

            event(new InvitationCancelled($invitation, $performer));

            return $invitation->fresh();
        });
    }

    // ─── Resend Invitation ───────────────────────────────────────

    /**
     * Resend a pending invitation (generates new token + resets TTL).
     *
     * @throws BusinessException
     */
    public function resendInvitation(string $invitationId, User $performer): Invitation
    {
        $this->assertCanInvite($performer);

        $invitation = $this->findInvitationOrFail($invitationId, $performer->account_id);

        if (!$invitation->canResend()) {
            throw BusinessException::invitationCannotResend();
        }

        if ($invitation->send_count >= self::MAX_RESEND_COUNT) {
            throw new BusinessException(
                'تم تجاوز الحد الأقصى لإعادة إرسال الدعوات. يرجى إلغاء الدعوة وإنشاء واحدة جديدة.',
                'ERR_INVITATION_MAX_RESEND',
                429
            );
        }

        return DB::transaction(function () use ($invitation, $performer) {
            // Generate new token and reset TTL
            $invitation->update([
                'token'       => $this->generateSecureToken(),
                'expires_at'  => now()->addHours(self::DEFAULT_TTL_HOURS),
                'last_sent_at' => now(),
                'send_count'  => $invitation->send_count + 1,
            ]);

            $this->logAction(
                $performer->account_id,
                $performer->id,
                'invitation.resent',
                'Invitation',
                $invitation->id,
                null,
                [
                    'send_count' => $invitation->send_count,
                    'new_expires_at' => $invitation->expires_at->toISOString(),
                ]
            );

            event(new InvitationResent($invitation, $performer));

            return $invitation->fresh();
        });
    }

    // ─── List Invitations ────────────────────────────────────────

    /**
     * List invitations for the current account with optional filters.
     */
    public function listInvitations(
        string $accountId,
        array $filters = []
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $query = Invitation::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->with(['role', 'inviter']);

        // Auto-expire stale pending invitations
        $this->expireStaleInvitations($accountId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortBy, $sortDir)
                     ->paginate($filters['per_page'] ?? 15);
    }

    // ─── Get Single Invitation ───────────────────────────────────

    /**
     * Get a single invitation by ID (tenant-scoped).
     *
     * @throws BusinessException
     */
    public function getInvitation(string $invitationId, string $accountId): Invitation
    {
        return $this->findInvitationOrFail($invitationId, $accountId);
    }

    /**
     * Get invitation details by token (public - used by the invitee).
     * Returns limited info: account name, role name, email, expiry.
     *
     * @throws BusinessException
     */
    public function getInvitationByToken(string $token): Invitation
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->with(['account:id,name', 'role:id,display_name'])
            ->where('token', $token)
            ->first();

        if (!$invitation) {
            throw BusinessException::invitationNotFound();
        }

        // Auto-expire if past TTL
        if ($invitation->isPending() && $invitation->expires_at->isPast()) {
            $invitation->update(['status' => Invitation::STATUS_EXPIRED]);
            $invitation->refresh();
        }

        return $invitation;
    }

    // ─── Expire Stale Invitations (batch) ────────────────────────

    /**
     * Mark all pending invitations that have passed their TTL as expired.
     */
    public function expireStaleInvitations(?string $accountId = null): int
    {
        $query = Invitation::withoutGlobalScopes()
            ->where('status', Invitation::STATUS_PENDING)
            ->where('expires_at', '<', now());

        if ($accountId) {
            $query->where('account_id', $accountId);
        }

        return $query->update(['status' => Invitation::STATUS_EXPIRED]);
    }

    // ─── Private Helpers ─────────────────────────────────────────

    /**
     * Assert the performer has permission to invite users.
     *
     * @throws BusinessException
     */
    private function assertCanInvite(User $performer): void
    {
        // Owner always can; otherwise check users:invite permission
        if (!$performer->is_owner && !$performer->hasPermission('users:invite')) {
            throw BusinessException::permissionDenied();
        }
    }

    /**
     * Validate role belongs to the same account.
     *
     * @throws BusinessException
     */
    private function assertRoleBelongsToAccount(string $roleId, string $accountId): void
    {
        $exists = Role::withoutGlobalScopes()
            ->where('id', $roleId)
            ->where('account_id', $accountId)
            ->exists();

        if (!$exists) {
            throw new BusinessException(
                'الدور المحدد غير موجود في هذا الحساب.',
                'ERR_ROLE_NOT_FOUND',
                404
            );
        }
    }

    /**
     * Find an invitation within the same account or throw.
     *
     * @throws BusinessException
     */
    private function findInvitationOrFail(string $invitationId, string $accountId): Invitation
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('id', $invitationId)
            ->first();

        if (!$invitation) {
            throw BusinessException::invitationNotFound();
        }

        return $invitation;
    }

    /**
     * Generate a cryptographically secure token for the invitation.
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Write to audit log.
     */
    private function logAction(
        string $accountId,
        string $userId,
        string $action,
        string $entityType,
        string $entityId,
        ?array $oldValues,
        ?array $newValues
    ): void {
        AuditLog::withoutGlobalScopes()->create([
            'account_id'  => $accountId,
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
