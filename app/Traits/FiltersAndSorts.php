<?php

namespace App\Traits;

use Closure;
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
     * Supports both simple column names and closures for relation searches:
     *   $this->applySearch($query, $request, [
     *       'title',
     *       'description',
     *       fn ($q, $search) => $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")),
     *   ]);
     */
    protected function applySearch(Builder|Relation $query, Request $request, array $searchableFields): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = $request->str('search')->toString();

        $query->where(function (Builder $q) use ($search, $searchableFields): void {
            foreach ($searchableFields as $field) {
                if ($field instanceof Closure) {
                    $field($q, $search);
                } else {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            }
        });
    }

    /**
     * Apply one of the whitelisted sort orders using the "-field" convention
     * (e.g. "-starts_at" for descending). Unknown values fall back to the
     * given default sort key.
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

    /**
     * Apply simple equality filters from request parameters.
     *
     * Maps request keys to database columns. Supports closures for custom
     * operators (e.g. date ranges):
     *   $this->applyFilters($query, $request, [
     *       'status' => 'status',
     *       'event_id' => 'event_id',
     *       'date_from' => fn ($q, $v) => $q->where('created_at', '>=', $v),
     *       'date_to' => fn ($q, $v) => $q->where('created_at', '<=', $v),
     *   ]);
     */
    protected function applyFilters(Builder|Relation $query, Request $request, array $filterMap): void
    {
        foreach ($filterMap as $requestKey => $columnOrClosure) {
            if ($request->filled($requestKey)) {
                $value = $request->input($requestKey);

                if ($columnOrClosure instanceof Closure) {
                    $columnOrClosure($query, $value);
                } else {
                    $query->where($columnOrClosure, $value);
                }
            }
        }
    }
}
