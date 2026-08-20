<?php

namespace App\Http\Controllers\Web\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginatesFilteredLists
{
    /**
     * @return list<int>
     */
    protected function perPageOptions(): array
    {
        return [10, 15, 20, 50];
    }

    protected function resolvePerPage(Request $request, int $default = 15): int
    {
        $perPage = (int) $request->integer('per_page', $default);

        return in_array($perPage, $this->perPageOptions(), true) ? $perPage : $default;
    }

    /**
     * Paginate using a precomputed total so the list query does not run a second COUNT.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function paginateFiltered(Builder $query, int $total, Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = $this->resolvePerPage($request, $perPage);
        $page = max(1, (int) $request->integer('page', 1));

        return (new LengthAwarePaginator(
            $query->forPage($page, $perPage)->get(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url()]
        ))->withQueryString();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function queryHasJoin(Builder $query, string $table): bool
    {
        foreach ($query->getQuery()->joins ?? [] as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }
}
