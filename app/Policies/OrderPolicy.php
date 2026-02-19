<?php
namespace App\Policies;

use App\Models\{User, Order};

class OrderPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('orders.view'); }
    public function view(User $user, Order $order): bool { return $user->account_id === $order->account_id && $user->hasPermission('orders.view'); }
    public function create(User $user): bool { return $user->hasPermission('orders.create'); }
    public function update(User $user, Order $order): bool { return $user->account_id === $order->account_id && $user->hasPermission('orders.update'); }
    public function cancel(User $user, Order $order): bool { return $user->account_id === $order->account_id && $user->hasPermission('orders.cancel') && $order->status->canCancel(); }
    public function ship(User $user, Order $order): bool { return $user->account_id === $order->account_id && $user->hasPermission('orders.ship') && $order->status->canShip(); }
}
