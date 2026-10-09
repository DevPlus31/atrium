<?php

declare(strict_types=1);

namespace App\Modules;

use App\Models\User;
use App\Modules\Data\SearchGroupData;
use App\Modules\Data\SearchResultData;
use Closure;
use InvalidArgumentException;

/**
 * The global search (the admin command palette): each module adds a
 * searcher for its records; a query asks every searcher the user may use.
 */
final class SearchRegistry
{
    /**
     * @var list<array{module: string, label: string, icon: string|null, searcher: (Closure(string, User, int): list<SearchResultData>)|class-string, permission: string|null, sort: int}>
     */
    private array $searchers = [];

    /**
     * Add a searcher: a closure or an invokable class receiving the search
     * term, the user and the maximum number of results, and returning
     * SearchResultData. The label (a translation key) heads its results.
     *
     * @param  (Closure(string, User, int): list<SearchResultData>)|class-string  $searcher
     */
    public function add(
        string $module,
        string $label,
        Closure|string $searcher,
        ?string $icon = null,
        ?string $permission = null,
        int $sort = 0,
    ): void {
        $this->searchers[] = [
            'module' => $module,
            'label' => $label,
            'icon' => $icon,
            'searcher' => $searcher,
            'permission' => $permission,
            'sort' => $sort,
        ];
    }

    /**
     * The results of every searcher the user may use, grouped and sorted;
     * searchers that found nothing are left out.
     *
     * @return list<SearchGroupData>
     */
    public function search(User $user, string $term, int $limit = 5): array
    {
        $permitted = array_values(array_filter(
            $this->searchers,
            static fn (array $searcher): bool => ModuleSwitch::isOn($searcher['module'], $user)
                && ($searcher['permission'] === null || $user->can($searcher['permission'])),
        ));

        usort($permitted, static fn (array $a, array $b): int => [$a['sort'], $a['label']] <=> [$b['sort'], $b['label']]);

        $groups = [];

        foreach ($permitted as $searcher) {
            $results = array_slice($this->run($searcher['searcher'], $term, $user, $limit), 0, $limit);

            if ($results !== []) {
                $groups[] = new SearchGroupData(
                    label: $this->translate($searcher['label']),
                    icon: $searcher['icon'],
                    results: $results,
                );
            }
        }

        return $groups;
    }

    /**
     * @param  (Closure(string, User, int): list<SearchResultData>)|class-string  $searcher
     * @return list<SearchResultData>
     */
    private function run(Closure|string $searcher, string $term, User $user, int $limit): array
    {
        $callable = $searcher instanceof Closure ? $searcher : resolve($searcher);

        if (! is_callable($callable)) {
            throw new InvalidArgumentException(sprintf('Searcher [%s] must be invokable.', $searcher));
        }

        $results = $callable($term, $user, $limit);

        throw_if(! is_array($results) || ! array_is_list($results), InvalidArgumentException::class, 'A searcher must return a list of SearchResultData.');

        foreach ($results as $result) {
            throw_unless($result instanceof SearchResultData, InvalidArgumentException::class, 'A searcher must return a list of SearchResultData.');
        }

        return $results;
    }

    private function translate(string $label): string
    {
        $translated = __($label);

        return is_string($translated) ? $translated : $label;
    }
}
