<?php
namespace App\Policies;

use App\Models\{User, Shipment};

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('shipments.view');
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->account_id === $shipment->account_id
            && $user->hasPermission('shipments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('shipments.create');
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->account_id === $shipment->account_id
            && $user->hasPermission('shipments.update')
            && !$shipment->status->isFinal();
    }

    public function cancel(User $user, Shipment $shipment): bool
    {
        return $user->account_id === $shipment->account_id
            && $user->hasPermission('shipments.cancel')
            && $shipment->status->isCancellable();
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->account_id === $shipment->account_id
            && $user->hasPermission('shipments.delete')
            && $shipment->status->value === 'draft';
    }

    public function printLabel(User $user, Shipment $shipment): bool
    {
        return $user->account_id === $shipment->account_id
            && $user->hasPermission('shipments.label');
    }

    public function bulkImport(User $user): bool
    {
        return $user->hasPermission('shipments.bulk');
    }
}
