<?php

declare(strict_types=1);

namespace Modules\Users\Http\Controllers;

use App\Models\User;
use App\Modules\RoleOptions;
use App\Modules\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Gate;
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

final readonly class UserController
{
    #[Authorize('viewAny', User::class)]
    public function index(UsersIndexQuery $query): Response
    {
        return Inertia::render('users::index', [
            'users' => UserData::collect($query->paginate(), PaginatedDataCollection::class),
            'roles' => RoleOptions::roleNames(),
            'can' => ['create' => Gate::allows('create', User::class), 'export' => Gate::allows('export', User::class)],
        ]);
    }

    #[Authorize('create', User::class)]
    public function create(): Response
    {
        return Inertia::render('users::create', [
            'roles' => RoleOptions::roleNames(),
        ]);
    }

    #[Authorize('create', User::class)]
    public function store(StoreUserRequest $request, CreateUser $action): RedirectResponse
    {
        $action->handle(
            name: $request->name(),
            email: $request->email(),
            password: $request->password(),
            roles: $request->roles(),
        );

        Toast::success(__('User created.'));

        return to_route('admin.users.index');
    }

    #[Authorize('update', 'user')]
    public function edit(User $user): Response
    {
        return Inertia::render('users::edit', [
            'user' => UserData::from($user->load('roles')),
            'roles' => RoleOptions::roleNames(),
        ]);
    }

    #[Authorize('update', 'user')]
    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $action->handle(
            user: $user,
            name: $request->name(),
            email: $request->email(),
            roles: $request->roles(),
        );

        Toast::success(__('User updated.'));

        return to_route('admin.users.index');
    }

    #[Authorize('delete', 'user')]
    public function destroy(User $user, DeleteUser $action): RedirectResponse
    {
        $action->handle($user);

        Toast::success(__('User deleted.'));

        return to_route('admin.users.index');
    }
}
