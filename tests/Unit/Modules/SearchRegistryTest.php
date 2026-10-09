<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Data\SearchGroupData;
use App\Modules\Data\SearchResultData;
use App\Modules\SearchRegistry;
use Illuminate\Support\Facades\Gate;
use Laravel\Pennant\Feature;
use Tests\Fixtures\Modules\TestModule\Search\NotInvokableSearch;
use Tests\Fixtures\Modules\TestModule\Search\TestModuleSearch;

beforeEach(function (): void {
    Feature::define('module:alpha', fn (): bool => true);
    Feature::define('module:bravo', fn (): bool => true);
});

function hit(string $title): SearchResultData
{
    return new SearchResultData(title: $title, description: null, url: 'https://atrium.test/'.$title);
}

it('groups the results of each searcher under its label', function (): void {
    $registry = new SearchRegistry();
    $registry->add(module: 'alpha', label: 'Things', searcher: TestModuleSearch::class, icon: 'package');

    $groups = $registry->search(User::factory()->create(['name' => 'Ada']), 'tea', limit: 2);

    expect($groups)->toHaveCount(1)
        ->and($groups[0]->label)->toBe('Things')
        ->and($groups[0]->icon)->toBe('package')
        ->and(array_map(fn (SearchResultData $result): string => $result->title, $groups[0]->results))->toBe(['tea 1', 'tea 2'])
        ->and($groups[0]->results[0]->description)->toBe('Ada');
});

it('passes the term, the user and the limit to closures', function (): void {
    $registry = new SearchRegistry();
    $user = User::factory()->create();
    $received = null;
    $registry->add(module: 'alpha', label: 'Things', searcher: function (string $term, User $by, int $limit) use (&$received): array {
        $received = [$term, $by->id, $limit];

        return [hit('one')];
    });

    $registry->search($user, 'tea', limit: 3);

    expect($received)->toBe(['tea', $user->id, 3]);
});

it('leaves out searchers that found nothing', function (): void {
    $registry = new SearchRegistry();
    $registry->add(module: 'alpha', label: 'Empty', searcher: fn (): array => []);
    $registry->add(module: 'alpha', label: 'Full', searcher: fn (): array => [hit('one')]);

    expect(array_map(fn (SearchGroupData $group): string => $group->label, $registry->search(User::factory()->create(), 'one')))->toBe(['Full']);
});

it('skips searchers the user may not use or whose module is off', function (): void {
    Gate::define('secret.view', fn (User $user): bool => false);
    $registry = new SearchRegistry();
    $registry->add(module: 'alpha', label: 'Secret', searcher: fn (): array => [hit('a')], permission: 'secret.view');
    $registry->add(module: 'bravo', label: 'Disabled', searcher: fn (): array => [hit('b')]);
    $registry->add(module: 'alpha', label: 'Open', searcher: fn (): array => [hit('c')]);

    $user = User::factory()->create();
    Feature::for($user)->deactivate('module:bravo');

    expect(array_map(fn (SearchGroupData $group): string => $group->label, $registry->search($user, 'x')))->toBe(['Open']);
});

it('orders groups by sort, then label', function (): void {
    $registry = new SearchRegistry();
    $registry->add(module: 'alpha', label: 'Zeta', searcher: fn (): array => [hit('z')], sort: 1);
    $registry->add(module: 'alpha', label: 'Beta', searcher: fn (): array => [hit('b')], sort: 2);
    $registry->add(module: 'alpha', label: 'Alpha', searcher: fn (): array => [hit('a')], sort: 1);

    expect(array_map(fn (SearchGroupData $group): string => $group->label, $registry->search(User::factory()->create(), 'x')))
        ->toBe(['Alpha', 'Zeta', 'Beta']);
});

it('rejects searchers that cannot be called or return something else', function (Closure|string $searcher, string $message): void {
    $registry = new SearchRegistry();
    $registry->add(module: 'alpha', label: 'Broken', searcher: $searcher);

    expect(fn (): array => $registry->search(User::factory()->create(), 'x'))
        ->toThrow(InvalidArgumentException::class, $message);
})->with([
    'not invokable' => [NotInvokableSearch::class, sprintf('Searcher [%s] must be invokable.', NotInvokableSearch::class)],
    // Pest calls closures in datasets, so each searcher comes wrapped once.
    'not a list' => [fn (): Closure => fn (): array => ['a' => hit('a')], 'A searcher must return a list of SearchResultData.'],
    'not results' => [fn (): Closure => fn (): array => ['a'], 'A searcher must return a list of SearchResultData.'],
    'not an array' => [fn (): Closure => fn (): string => 'a', 'A searcher must return a list of SearchResultData.'],
]);
