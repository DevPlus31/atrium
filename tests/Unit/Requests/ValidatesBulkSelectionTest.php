<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\Models\CodeRecord;
use Tests\Fixtures\Models\UlidRecord;
use Tests\Fixtures\Requests\BulkSelectionRequest;

/**
 * @param  list<string>  $ids
 */
function bulkSelection(array $ids, User $user): BulkSelectionRequest
{
    $request = BulkSelectionRequest::create('/', 'DELETE', ['ids' => $ids]);
    $request->setContainer(app())->setRedirector(resolve(Redirector::class));
    $request->setUserResolver(fn (): User => $user);
    $request->validateResolved();

    return $request;
}

it('keeps the permitted records with integer keys and skips malformed ids', function (): void {
    $editor = Role::findOrCreate('editor');
    $viewer = Role::findOrCreate('viewer');
    Gate::define('remove', fn (User $user, Role $role): bool => $role->name === 'editor');

    $request = bulkSelection([(string) $editor->id, (string) $viewer->id, 'abc'], User::factory()->create());
    $permitted = $request->permitted(Role::query(), 'remove');

    expect($permitted->pluck('name')->all())->toBe(['editor'])
        ->and($request->skipped($permitted->count()))->toBe(2);
});

it("matches each model's own key shape", function (string $model, array $keys, array $selected, array $expected): void {
    Gate::define('remove', fn (): bool => true);
    Schema::create((new $model)->getTable(), function (Blueprint $table): void {
        $table->string('id')->primary();
    });

    foreach ($keys as $key) {
        $model::query()->insert(['id' => $key]);
    }

    $request = bulkSelection($selected, User::factory()->create());

    expect($request->permitted($model::query(), 'remove')->pluck('id')->all())->toBe($expected);
})->with([
    'ULID keys skip UUIDs' => [UlidRecord::class, ['01J9ZZ9ZZ9ZZ9ZZ9ZZ9ZZ9ZZ9Z'], ['01J9ZZ9ZZ9ZZ9ZZ9ZZ9ZZ9ZZ9Z', '01a11de1-b4a5-7201-88bd-86113b718be3'], ['01J9ZZ9ZZ9ZZ9ZZ9ZZ9ZZ9ZZ9Z']],
    'free-form keys take any non-empty id' => [CodeRecord::class, ['EUR', 'USD'], ['EUR', 'GBP'], ['EUR']],
]);

it('skips ULIDs and junk for UUID keys', function (): void {
    Gate::define('remove', fn (): bool => true);
    $user = User::factory()->create();

    $request = bulkSelection([$user->id, (string) Str::ulid(), 'not-an-id'], $user);

    expect($request->permitted(User::query(), 'remove')->pluck('id')->all())->toBe([$user->id]);
});
