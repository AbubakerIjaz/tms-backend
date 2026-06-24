<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesListingFilters
{
    protected function listingPerPage(Request $request, int $default = 10): int
    {
        return min(max((int) $request->query('per_page', $default), 5), 50);
    }

  /** @param Builder<\Illuminate\Database\Eloquent\Model> $query */
    protected function applyDateRangeFilter(Builder $query, Request $request, string $column): void
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if ($from) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to) {
            $query->whereDate($column, '<=', $to);
        }
    }

    protected function searchTerm(Request $request): string
    {
        return trim((string) $request->query('search', ''));
    }

    /** @param array<int, string> $columns */
    protected function applyColumnSearch(Builder $query, string $search, array $columns): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->{$method}($column, 'ilike', "%{$search}%");
            }
        });
    }
}
