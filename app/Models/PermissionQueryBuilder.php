<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Permission model.
 *
 * Normalises colon-notation keys (roles:manage → roles.manage) in WHERE
 * and WHERE IN conditions on the `key` column so that test code written
 * with either notation can query the dot-notation DB records correctly.
 */
class PermissionQueryBuilder extends Builder
{
    private static function normalizeKey(mixed $value): mixed
    {
        return is_string($value) ? str_replace(':', '.', $value) : $value;
    }

    /**
     * {@inheritdoc}
     *
     * When the column is `key`, normalise colon-notation values to dot-notation
     * before delegating to the parent builder. Handles both the two-argument
     * form `where('key', 'value')` and the three-argument form
     * `where('key', '=', 'value')`.
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        $numArgs = func_num_args();

        if (is_string($column) && $column === 'key') {
            if ($numArgs === 2 && is_string($operator)) {
                // Two-arg form: where('key', 'roles:manage') — $operator IS the value.
                return parent::where($column, self::normalizeKey($operator));
            }

            if ($numArgs >= 3 && is_string($value)) {
                // Three-arg form: where('key', '=', 'roles:manage')
                return parent::where($column, $operator, self::normalizeKey($value), $boolean);
            }
        }

        // Default — preserve exact arg count to keep parent's 2-arg detection working.
        if ($numArgs === 2) {
            return parent::where($column, $operator);
        }
        if ($numArgs === 3) {
            return parent::where($column, $operator, $value);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * {@inheritdoc}
     *
     * Normalises colon-notation values in a whereIn on the `key` column.
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false): static
    {
        if (is_string($column) && $column === 'key' && is_array($values)) {
            $values = array_map([self::class, 'normalizeKey'], $values);
        }

        return parent::whereIn($column, $values, $boolean, $not);
    }
}
