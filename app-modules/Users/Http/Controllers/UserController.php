<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Users\Actions\CreateUser;
use Modules\Users\Actions\DeleteUser;
use Modules\Users\Actions\UpdateUser;
use Modules\Users\Data\UserData;
use Modules\Users\Http\Requests\StoreUserRequest;
use Modules\Users\Http\Requests\UpdateUserRequest;
use Modules\Users\Queries\UsersIndexQuery;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\Permission\Models\Role;

final readonly class UserController
{
    #[Authorize('viewAny', User::class)]
    public function index(UsersIndexQuery $query): Response
    {
        return Inertia::render('users::index', [
            'users' => UserData::collect($query->paginate(), PaginatedDataCollection::class),
            'roles' => $this->roleNames(),
        ]);
    }

    #[Authorize('create', User::class)]
    public function create(): Response
    {
        return Inertia::render('users::create', [
            'roles' => $this->roleNames(),
        ]);
    }

    #[Authorize('create', User::class)]
    public function store(StoreUserRequest $request, CreateUser $action): RedirectResponse
    {
        $action->handle(
            name: $request->string('name')->value(),
            email: $request->string('email')->value(),
            password: $request->string('password')->value(),
            roles: $request->roles(),
        );

        return to_route('admin.users.index')->with('success', __('User created.'));
    }

    #[Authorize('update', 'user')]
    public function edit(User $user): Response
    {
        return Inertia::render('users::edit', [
            'user' => UserData::from($user->load('roles')),
            'roles' => $this->roleNames(),
        ]);
    }

    #[Authorize('update', 'user')]
    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $action->handle(
            user: $user,
            name: $request->string('name')->value(),
            email: $request->string('email')->value(),
            roles: $request->roles(),
        );

        return to_route('admin.users.index')->with('success', __('User updated.'));
    }

    #[Authorize('delete', 'user')]
    public function destroy(User $user, DeleteUser $action): RedirectResponse
    {
        $action->handle($user);

        return to_route('admin.users.index')->with('success', __('User deleted.'));
    }

    /**
     * @return list<string>
     */
    private function roleNames(): array
    {
        /** @var list<string> $names */
        $names = Role::query()->orderBy('name')->pluck('name')->all();

        return $names;
    }
}
