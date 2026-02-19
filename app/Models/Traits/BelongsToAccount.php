<?php

namespace App\Models\Traits;

use App\Models\Scopes\AccountScope;

/**
 * Trait BelongsToAccount
 *
 * Apply this trait to any model that must be isolated per tenant.
 * It automatically adds a global scope filtering by account_id.
 */
trait BelongsToAccount
{
    public static function bootBelongsToAccount(): void
    {
        static::addGlobalScope(new AccountScope());

        // Auto-assign account_id on creation
        static::creating(function ($model) {
            if (empty($model->account_id) && app()->has('current_account_id')) {
                $model->account_id = app('current_account_id');
            }
        });
    }
}
