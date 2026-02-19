<?php
namespace App\Policies;

use App\Models\{User, Wallet};

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool { return $user->account_id === $wallet->account_id && $user->hasPermission('wallet.view'); }
    public function topup(User $user): bool { return $user->hasPermission('wallet.topup'); }
    public function hold(User $user): bool { return $user->hasPermission('wallet.hold'); }
    public function capture(User $user): bool { return $user->hasPermission('wallet.capture'); }
    public function reconcile(User $user): bool { return $user->hasPermission('wallet.reconcile'); }
    public function viewStatement(User $user): bool { return $user->hasPermission('wallet.statement'); }
}
