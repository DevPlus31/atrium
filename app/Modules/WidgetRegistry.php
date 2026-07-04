<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\User;
use Closure;
use InvalidArgumentException;
use Laravel\Pennant\Feature;
use Spatie\LaravelData\Data;

final class WidgetRegistry
{
    /**
     * @var list<array{module: string, key: string, resolver: (Closure(): Data)|class-string, permission: string|null, sort: int}>
     */
    private array $widgets = [];

    /**
     * Declare a widget whose resolver returns a data object for the dashboard.
     *
     * @param  (Closure(): Data)|class-string  $resolver
     */
    public function declare(
        string $module,
        string $key,
        Closure|string $resolver,
        ?string $permission = null,
        int $sort = 0,
    ): void {
        $this->widgets[] = [
            'module' => $module,
            'key' => $key,
            'resolver' => $resolver,
            'permission' => $permission,
            'sort' => $sort,
        ];
    }

    /**
     * The resolved widgets the given user is permitted to see, sorted by sort order.
     *
     * @return list<array{key: string, sort: int, data: Data}>
     */
    public function widgetsFor(User $user): array
    {
        return array_map(static fn (array $widget): array => [
            'key' => $widget['key'],
            'sort' => $widget['sort'],
            'data' => self::resolve($widget['resolver']),
        ], $this->permittedFor($user));
    }

    /**
     * The descriptors of the widgets the given user is permitted to see,
     * sorted by sort order, without resolving their data.
     *
     * @return list<array{key: string, sort: int}>
     */
    public function descriptorsFor(User $user): array
    {
        return array_map(static fn (array $widget): array => [
            'key' => $widget['key'],
            'sort' => $widget['sort'],
        ], $this->permittedFor($user));
    }

    /**
     * Resolve the data object of a single widget the given user is permitted
     * to see, or null when the widget is unknown or not permitted.
     */
    public function resolveFor(User $user, string $key): ?Data
    {
        foreach ($this->permittedFor($user) as $widget) {
            if ($widget['key'] === $key) {
                return self::resolve($widget['resolver']);
            }
        }

        return null;
    }

    /**
     * @param  (Closure(): Data)|class-string  $resolver
     */
    private static function resolve(Closure|string $resolver): Data
    {
        if ($resolver instanceof Closure) {
            return $resolver();
        }

        $instance = resolve($resolver);

        if (! is_callable($instance)) {
            throw new InvalidArgumentException(sprintf('Widget resolver [%s] must be invokable.', $resolver));
        }

        $data = $instance();

        if (! $data instanceof Data) {
            throw new InvalidArgumentException(sprintf('Widget resolver [%s] must return a data object.', $resolver));
        }

        return $data;
    }

    /**
     * The widgets the given user is permitted to see, sorted by sort order.
     *
     * @return list<array{module: string, key: string, resolver: (Closure(): Data)|class-string, permission: string|null, sort: int}>
     */
    private function permittedFor(User $user): array
    {
        $permitted = array_values(array_filter(
            $this->widgets,
            static fn (array $widget): bool => Feature::for($user)->active('module:'.$widget['module'])
                && ($widget['permission'] === null || $user->can($widget['permission'])),
        ));

        usort($permitted, static fn (array $a, array $b): int => [$a['sort'], $a['key']] <=> [$b['sort'], $b['key']]);

        return $permitted;
    }
}
