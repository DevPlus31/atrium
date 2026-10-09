<?php

declare(strict_types=1);

namespace App\Modules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The one text match used by index-page search filters and command-palette
 * searchers: case-insensitive "contains" on any of the columns (whereLike,
 * the same on SQLite and PostgreSQL).
 */
final readonly class TextSearch
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  non-empty-list<string>  $columns
     * @return Builder<TModel>
     */
    public static function whereLikeAny(Builder $query, array $columns, string $term): Builder
    {
        return $query->where(function (Builder $query) use ($columns, $term): void {
            foreach ($columns as $column) {
                $query->orWhereLike($column, '%'.$term.'%');
            }
        });
    }
}
