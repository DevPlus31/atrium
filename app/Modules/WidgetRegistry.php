<?php

declare(strict_types=1);

namespace App\Modules;

use App\Enums\Area;
use App\Models\User;
use Closure;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

final class WidgetRegistry
{
    /**
     * @var list<array{module: string, key: string, resolver: (Closure(User): ?Data)|class-string, permission: string|null, sort: int, area: Area}>
     */
    private array $widgets = [];

    /**
     * Declare a widget whose resolver returns a data object for a dashboard.
     * The resolver receives the viewing user; returning null hides the
     * widget for that user.
     *
     * @param  (Closure(User): ?Data)|class-string  $resolver
     */
    public function declare(
        string $module,
        string $key,
        Closure|string $resolver,
        ?string $permission = null,
        int $sort = 0,
        Area $area = Area::Admin,
    ): void {
        $this->widgets[] = [
            'module' => $module,
            'key' => $key,
            'resolver' => $resolver,
            'permission' => $permission,
            'sort' => $sort,
            'area' => $area,
        ];
    }

    /**
     * The descriptors of the widgets the given user is permitted to see in
     * the given area, sorted by sort order, without resolving their data.
     *
     * @return list<array{key: string, sort: int}>
     */
    public function descriptorsFor(User $user, Area $area = Area::Admin): array
    {
        return array_map(static fn (array $widget): array => [
            'key' => $widget['key'],
            'sort' => $widget['sort'],
        ], $this->permittedFor($user, $area));
    }

    /**
     * Resolve the data object of a single widget the given user is permitted
     * to see in the given area, or null when the widget is unknown, not
     * permitted, or has nothing to show for this user.
     */
    public function resolveFor(User $user, string $key, Area $area = Area::Admin): ?Data
    {
        foreach ($this->permittedFor($user, $area) as $widget) {
            if ($widget['key'] === $key) {
                return $this->resolve($widget['resolver'], $user);
            }
        }

        return null;
    }

    /**
     * @param  (Closure(User): ?Data)|class-string  $resolver
     */
    private function resolve(Closure|string $resolver, User $user): ?Data
    {
        if ($resolver instanceof Closure) {
            return $resolver($user);
        }

        $instance = resolve($resolver);

        if (! is_callable($instance)) {
            throw new InvalidArgumentException(sprintf('Widget resolver [%s] must be invokable.', $resolver));
        }

        $data = $instance($user);

        if ($data !== null && ! $data instanceof Data) {
            throw new InvalidArgumentException(sprintf('Widget resolver [%s] must return a data object or null.', $resolver));
        }

        return $data;
    }

    /**
     * The widgets of the given area the user is permitted to see, sorted by
     * sort order.
     *
     * @return list<array{module: string, key: string, resolver: (Closure(User): ?Data)|class-string, permission: string|null, sort: int, area: Area}>
     */
    private function permittedFor(User $user, Area $area): array
    {
        $permitted = array_values(array_filter(
            $this->widgets,
            static fn (array $widget): bool => $widget['area'] === $area
                && ModuleSwitch::isOn($widget['module'], $user)
                && ($widget['permission'] === null || $user->can($widget['permission'])),
        ));

        usort($permitted, static fn (array $a, array $b): int => [$a['sort'], $a['key']] <=> [$b['sort'], $b['key']]);

        return $permitted;
    }
}
