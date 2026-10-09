<?php

declare(strict_types=1);

namespace Modules\Roles\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;
use Spatie\Permission\Models\Role;

/**
 * Finds roles by name for the command palette.
 */
final readonly class RolesSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        $found = Role::query()
            ->whereLike('name', '%'.$term.'%')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return array_values($found->map(fn (Role $role): SearchResultData => new SearchResultData(
            title: $role->name,
            description: null,
            url: $user->can('update', $role)
                ? route('admin.roles.edit', $role)
                : route('admin.roles.index', ['filter' => ['search' => $role->name]]),
        ))->all());
    }
}
