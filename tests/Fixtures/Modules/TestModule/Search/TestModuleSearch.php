<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\TestModule\Search;

use App\Models\User;
use App\Modules\Data\SearchResultData;

final readonly class TestModuleSearch
{
    /**
     * @return list<SearchResultData>
     */
    public function __invoke(string $term, User $user, int $limit): array
    {
        return array_map(
            static fn (int $index): SearchResultData => new SearchResultData(
                title: sprintf('%s %d', $term, $index),
                description: $user->name,
                url: sprintf('https://atrium.test/%d', $index),
            ),
            range(1, $limit + 2),
        );
    }
}
