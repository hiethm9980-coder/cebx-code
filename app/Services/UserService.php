<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Exceptions\BusinessException;
use App\Events\UserInvited;
use App\Events\UserDisabled;
use App\Events\UserDeleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Add/invite a new user to the current account.
     *
     * @throws BusinessException
     */
    public function addUser(array $data, User $performer): User
    {
        $this->assertCanManageUsers($performer);

        $accountId = $performer->account_id;

        // Check if email already exists in this account
        $exists = User::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('email', $data['email'])
            ->exists();

        if ($exists) {
            throw BusinessException::duplicateEmail();
        }

        return DB::transaction(function () use ($data, $performer, $accountId) {

            $user = User::withoutGlobalScopes()->create([
                'account_id' => $accountId,
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password'] ?? Str::random(32)), // Securely hashed
                'phone'      => $data['phone'] ?? null,
                'status'     => 'active',
                'is_owner'   => false,
                'locale'     => $data['locale'] ?? 'en',
                'timezone'   => $data['timezone'] ?? 'UTC',
            ]);

            $this->logAction($accountId, $performer->id, 'user.added', 'User', $user->id, null, [
                'name'  => $user->name,
                'email' => $user->email,
            ]);

            // Fire event for email/SMS notification
            event(new UserInvited($user, $performer));

            return $user;
        });
    }

    /**
     * Disable a user — immediately prevents login.
     *
     * @throws BusinessException
     */
    public function disableUser(string $userId, User $performer): User
    {
        $this->assertCanManageUsers($performer);

        $user = $this->findUserOrFail($userId, $performer->account_id);

        // Cannot disable yourself
        if ($user->id === $performer->id) {
            throw BusinessException::cannotModifySelf('تعطيل');
        }

        // Cannot disable the account owner
        if ($user->is_owner) {
            throw BusinessException::cannotModifyOwner();
        }

        return DB::transaction(function () use ($user, $performer) {
            $oldStatus = $user->status;

            $user->update(['status' => 'inactive']);

            // Revoke all active tokens (immediately prevents API access)
            $user->tokens()->delete();

            $this->logAction(
                $performer->account_id,
                $performer->id,
                'user.disabled',
                'User',
                $user->id,
                ['status' => $oldStatus],
                ['status' => 'inactive']
            );

            event(new UserDisabled($user, $performer));

            return $user->fresh();
        });
    }

    /**
     * Re-enable a previously disabled user.
     *
     * @throws BusinessException
     */
    public function enableUser(string $userId, User $performer): User
    {
        $this->assertCanManageUsers($performer);

        $user = $this->findUserOrFail($userId, $performer->account_id);

        if ($user->status === 'active') {
            throw new BusinessException('المستخدم نشط بالفعل.', 'ERR_ALREADY_ACTIVE', 422);
        }

        return DB::transaction(function () use ($user, $performer) {
            $oldStatus = $user->status;

            $user->update(['status' => 'active']);

            $this->logAction(
                $performer->account_id,
                $performer->id,
                'user.enabled',
                'User',
                $user->id,
                ['status' => $oldStatus],
                ['status' => 'active']
            );

            return $user->fresh();
        });
    }

    /**
     * Delete a user (soft delete).
     * If user has elevated privileges (is_owner), block until responsibilities transferred.
     *
     * @throws BusinessException
     */
    public function deleteUser(string $userId, User $performer, bool $forceTransfer = false): bool
    {
        $this->assertCanManageUsers($performer);

        $user = $this->findUserOrFail($userId, $performer->account_id);

        // Cannot delete yourself
        if ($user->id === $performer->id) {
            throw BusinessException::cannotModifySelf('حذف');
        }

        // Cannot delete the account owner
        if ($user->is_owner) {
            throw BusinessException::cannotModifyOwner();
        }

        // Check if user has active responsibilities that need transfer
        if ($this->hasActiveResponsibilities($user) && !$forceTransfer) {
            throw new BusinessException(
                'يجب نقل مسؤوليات هذا المستخدم أولاً قبل الحذف.',
                'ERR_RESPONSIBILITY_TRANSFER_REQUIRED',
                409
            );
        }

        return DB::transaction(function () use ($user, $performer) {
            // Revoke all tokens
            $user->tokens()->delete();

            // Soft delete
            $user->delete();

            $this->logAction(
                $performer->account_id,
                $performer->id,
                'user.deleted',
                'User',
                $user->id,
                ['name' => $user->name, 'email' => $user->email],
                null
            );

            event(new UserDeleted($user, $performer));

            return true;
        });
    }

    /**
     * Update user profile information.
     *
     * @throws BusinessException
     */
    public function updateUser(string $userId, array $data, User $performer): User
    {
        $this->assertCanManageUsers($performer);

        $user = $this->findUserOrFail($userId, $performer->account_id);

        // Cannot change owner status via this method
        unset($data['is_owner']);

        // Whitelist allowed fields to prevent mass assignment
        $allowedFields = ['name', 'phone', 'locale', 'timezone'];
        $safeData = array_intersect_key($data, array_flip($allowedFields));

        return DB::transaction(function () use ($user, $safeData, $data, $performer) {
            $oldValues = $user->only(array_keys($safeData));

            $user->update($safeData);

            $this->logAction(
                $performer->account_id,
                $performer->id,
                'user.updated',
                'User',
                $user->id,
                $oldValues,
                $safeData
            );

            return $user->fresh();
        });
    }

    /**
     * List all users in the current account with optional filters.
     */
    public function listUsers(string $accountId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = User::withoutGlobalScopes()
            ->where('account_id', $accountId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        $allowedSortFields = ['name', 'email', 'status', 'created_at', 'last_login_at'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSortFields)
            ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDir)
                     ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get change history for users in this account.
     */
    public function getUserChangeLog(string $accountId, ?string $userId = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('entity_type', 'User')
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('entity_id', $userId);
        }

        return $query->paginate(20);
    }

    // ─── Private Helpers ──────────────────────────────────────────

    /**
     * Assert the performer has permission to manage users (must be owner or admin).
     *
     * @throws BusinessException
     */
    private function assertCanManageUsers(User $performer): void
    {
        // For now: only owner can manage. Will be extended with RBAC in FR-IAM-003.
        if (!$performer->is_owner) {
            throw new BusinessException(
                'لا تملك صلاحية كافية لإدارة المستخدمين.',
                'ERR_PERMISSION',
                403
            );
        }
    }

    /**
     * Find user within the same account or throw.
     *
     * @throws BusinessException
     */
    private function findUserOrFail(string $userId, string $accountId): User
    {
        $user = User::withoutGlobalScopes()
            ->where('account_id', $accountId)
            ->where('id', $userId)
            ->first();

        if (!$user) {
            throw new BusinessException(
                'المستخدم غير موجود.',
                'ERR_USER_NOT_FOUND',
                404
            );
        }

        return $user;
    }

    /**
     * Check if user has active responsibilities (API keys, pending tasks, etc.)
     * This will be expanded as more modules are built.
     */
    private function hasActiveResponsibilities(User $user): bool
    {
        // Check if user has active API tokens
        $hasTokens = $user->tokens()->count() > 0;

        // Future: check shipments assigned, pending approvals, etc.
        // $hasPendingShipments = Shipment::where('assigned_to', $user->id)->where('status', 'pending')->exists();

        return $hasTokens;
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
