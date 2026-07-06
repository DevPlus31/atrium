<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\User;
use App\Modules\Data\NavItemData;
use Laravel\Pennant\Feature;

final class NavRegistry
{
    /**
     * @var list<array{module: string, label: string, routeName: string, icon: string|null, permission: string|null, group: string|null, sort: int, external: bool}>
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
        bool $external = false,
    ): void {
        $this->items[] = [
            'module' => $module,
            'label' => $label,
            'routeName' => $routeName,
            'icon' => $icon,
            'permission' => $permission,
            'group' => $group,
            'sort' => $sort,
            'external' => $external,
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

        return array_map(fn (array $item): NavItemData => new NavItemData(
            label: $this->translate($item['label']),
            routeName: $item['routeName'],
            href: route($item['routeName']),
            icon: $item['icon'],
            group: $item['group'] === null ? null : $this->translate($item['group']),
            sort: $item['sort'],
            external: $item['external'],
        ), $visible);
    }

    /**
     * Registered labels are English strings that double as translation keys
     * (lang/*.json); translation happens here, at render time, so the active
     * request locale applies — module providers register labels at boot,
     * before the locale is known.
     */
    private function translate(string $label): string
    {
        $translated = __($label);

        return is_string($translated) ? $translated : $label;
    }
}
