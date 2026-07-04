<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\User;
use App\Modules\Data\NavItemData;
use Laravel\Pennant\Feature;

final class NavRegistry
{
    /**
     * @var list<array{module: string, label: string, routeName: string, icon: string|null, permission: string|null, group: string|null, sort: int}>
     */
    private array $items = [];

    public function add(
        string $module,
        string $label,
        string $routeName,
        ?string $icon = null,
        ?string $permission = null,
        ?string $group = null,
        int $sort = 0,
    ): void {
        $this->items[] = [
            'module' => $module,
            'label' => $label,
            'routeName' => $routeName,
            'icon' => $icon,
            'permission' => $permission,
            'group' => $group,
            'sort' => $sort,
        ];
    }

    /**
     * The navigation items visible to the given user, sorted by group and sort order.
     *
     * @return list<NavItemData>
     */
    public function itemsFor(User $user): array
    {
        $visible = array_values(array_filter(
            $this->items,
            static fn (array $item): bool => Feature::for($user)->active('module:'.$item['module'])
                && ($item['permission'] === null || $user->can($item['permission'])),
        ));

        usort($visible, static fn (array $a, array $b): int => [$a['group'] ?? '', $a['sort'], $a['label']] <=> [$b['group'] ?? '', $b['sort'], $b['label']]);

        return array_map(static fn (array $item): NavItemData => new NavItemData(
            label: $item['label'],
            routeName: $item['routeName'],
            href: route($item['routeName']),
            icon: $item['icon'],
            group: $item['group'],
            sort: $item['sort'],
        ), $visible);
    }
}
