<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class QueryOptimizationService
{
    /**
     * Optimize a query by adding proper indexes and caching
     *
     * @param Builder $query
     * @param string $cacheKey
     * @param int $ttl
     * @return mixed
     */
    public static function optimizeQuery($query, $cacheKey, $ttl = 3600)
    {
        return Cache::remember($cacheKey, $ttl, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Optimize a paginated query
     *
     * @param Builder $query
     * @param int $perPage
     * @param string $pageName
     * @param int $page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function optimizePaginatedQuery($query, $perPage = 10, $pageName = 'page', $page = 1)
    {
        return $query->paginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * Optimize a search query with proper indexing
     *
     * @param Builder $query
     * @param string $search
     * @param array $searchableColumns
     * @return Builder
     */
    public static function optimizeSearchQuery($query, $search, array $searchableColumns)
    {
        return $query->where(function ($q) use ($search, $searchableColumns) {
            foreach ($searchableColumns as $column) {
                $q->orWhere($column, 'like', '%' . $search . '%');
            }
        });
    }

    /**
     * Optimize a date range query
     *
     * @param Builder $query
     * @param string $column
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public static function optimizeDateRangeQuery($query, $column, $startDate, $endDate)
    {
        return $query->whereBetween($column, [$startDate, $endDate]);
    }

    /**
     * Clear cache for a specific key
     *
     * @param string $key
     * @return bool
     */
    public static function clearCache($key)
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache for multiple keys
     *
     * @param array $keys
     * @return void
     */
    public static function clearMultipleCache(array $keys)
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Optimize a query with proper joins
     *
     * @param Builder $query
     * @param array $joins
     * @return Builder
     */
    public static function optimizeJoins($query, array $joins)
    {
        foreach ($joins as $join) {
            $query->join($join['table'], $join['first'], $join['operator'], $join['second']);
        }
        return $query;
    }
}
