<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

trait FiltersAndSorts
{
    /**
     * Apply a grouped LIKE search across the given fields.
     *
     * The whole search is wrapped in one closure so it can never bypass
     * other where constraints (visibility, status, ownership…).
     *
     * @template TQuery of Builder|Relation
     *
     * @param  TQuery  $query
     * @param  array<int, string>  $searchableFields
     */
    protected function applySearch(Builder|Relation $query, Request $request, array $searchableFields): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = $request->str('search');

        $query->where(function (Builder $q) use ($search, $searchableFields): void {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply one of the whitelisted sort orders using the "-field" convention
     * (e.g. "-starts_at" for descending). Unknown values fall back to the
     * given default sort key.
     *
     * @template TQuery of Builder|Relation
     *
     * @param  TQuery  $query
     * @param  array<int, string>  $sortableFields
     */
    protected function applySort(Builder|Relation $query, Request $request, array $sortableFields, string $default = 'created_at'): void
    {
        $sort = $request->str('sort')->toString();

        if (! in_array($sort, $sortableFields, true) && ! in_array(ltrim($sort, '-'), $sortableFields, true)) {
            $sort = $default;
        }

        $column = ltrim($sort, '-');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $query->orderBy($column, $direction);
    }

    /**
     * Resolve the per-page value (min 1, max 50).
     */
    protected function perPage(Request $request, int $default = 15): int
    {
        return max(1, min(50, (int) $request->integer('per_page', $default)));
    }
}