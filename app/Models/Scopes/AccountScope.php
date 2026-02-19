<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * AccountScope ensures every query is filtered by the current tenant's account_id.
 * This is the core of the multi-tenancy isolation layer.
 */
class AccountScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->has('current_account_id')) {
            $builder->where(
                $model->getTable() . '.account_id',
                app('current_account_id')
            );
        }
    }
}
