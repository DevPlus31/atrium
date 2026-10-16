<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\PersonalDataRegistry;

final readonly class InvokableTestExporter
{
    /**
     * @return list<string>
     */
    public function __invoke(User $user): array
    {
        return [$user->name];
    }
}

final readonly class InvokableTestEraser
{
    public function __invoke(User $user): void
    {
        $user->forceFill(['name' => 'Erased']);
    }
}

it('exports every source, sorted by key', function (): void {
    $registry = new PersonalDataRegistry();
    $registry->add('zeta', 'zeta.items', fn (User $user): array => ['z']);
    $registry->add('alpha', 'alpha.items', InvokableTestExporter::class);

    $export = $registry->export(User::factory()->make(['name' => 'Ada']));

    expect($export)->toBe(['alpha.items' => ['Ada'], 'zeta.items' => ['z']])
        ->and(array_keys($export))->toBe(['alpha.items', 'zeta.items']);
});

it('runs the erasers of the sources that have one', function (): void {
    $registry = new PersonalDataRegistry();
    $registry->add('alpha', 'alpha.items', InvokableTestExporter::class, InvokableTestEraser::class);
    $registry->add('beta', 'beta.items', InvokableTestExporter::class);

    $user = User::factory()->make(['name' => 'Ada']);

    $registry->erase($user);

    expect($user->name)->toBe('Erased');
});

it('refuses a key used twice', function (): void {
    $registry = new PersonalDataRegistry();
    $registry->add('alpha', 'alpha.items', InvokableTestExporter::class);

    $registry->add('beta', 'alpha.items', InvokableTestExporter::class);
})->throws(InvalidArgumentException::class, 'Personal data source [alpha.items] is already registered.');

it('refuses a source that is not invokable', function (): void {
    $registry = new PersonalDataRegistry();
    $registry->add('alpha', 'alpha.items', stdClass::class);

    $registry->export(User::factory()->make());
})->throws(InvalidArgumentException::class, 'Personal data source [alpha.items] must be invokable.');

it('refuses an exporter that returns something other than an array', function (): void {
    $registry = new PersonalDataRegistry();
    $registry->add('alpha', 'alpha.items', fn (User $user): string => 'nope');

    $registry->export(User::factory()->make());
})->throws(InvalidArgumentException::class, 'Personal data exporter [alpha.items] must return an array.');

it('is filled by the module providers', function (): void {
    expect(resolve(PersonalDataRegistry::class)->export(User::factory()->create()))->toHaveKey('shop.orders');
});
