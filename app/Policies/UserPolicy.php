<?php
namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('users.view'); }
    public function view(User $user, User $target): bool { return $user->account_id === $target->account_id && $user->hasPermission('users.view'); }
    public function create(User $user): bool { return $user->hasPermission('users.create'); }
    public function update(User $user, User $target): bool { return $user->account_id === $target->account_id && $user->hasPermission('users.update') && $user->id !== $target->id; }
    public function delete(User $user, User $target): bool { return $user->account_id === $target->account_id && $user->hasPermission('users.delete') && $user->id !== $target->id && !$target->isSuperAdmin(); }
    public function suspend(User $user, User $target): bool { return $this->update($user, $target); }
    public function assignRole(User $user): bool { return $user->hasPermission('roles.assign'); }
}
