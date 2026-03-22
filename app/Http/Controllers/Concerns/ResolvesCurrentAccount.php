<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Account;
use App\Models\Shipment;

/**
 * Shared controller helpers for resolving the current authenticated account
 * and scoping resource lookups to the current tenant.
 *
 * Include via: use ResolvesCurrentAccount;
 */
trait ResolvesCurrentAccount
{
    /**
     * Resolve current account ID from the authenticated user.
     */
    protected function currentAccountId(): string
    {
        return request()->user()->account_id;
    }

    /**
     * Resolve current Account model (eager loads if needed).
     */
    protected function currentAccount(): Account
    {
        return request()->user()->account;
    }

    /**
     * Find a Shipment scoped to the current tenant — throws 404 if not found or wrong tenant.
     */
    protected function findShipmentForCurrentAccount(string $shipmentId): Shipment
    {
        return Shipment::where('id', $shipmentId)
            ->where('account_id', $this->currentAccountId())
            ->firstOrFail();
    }
}
