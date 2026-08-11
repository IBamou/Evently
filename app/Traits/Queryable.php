<?php

namespace App\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

trait Queryable
{
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

    protected function perPage(Request $request, int $default = 15): int
    {
        return max(1, min(50, (int) $request->integer('per_page', $default)));
    }
}
