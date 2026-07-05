<?php

declare(strict_types=1);

namespace Modules\Users\Policies;

use App\Models\User;

final readonly class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user): bool
    {
        return $user->can('users.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('users.delete') && $user->isNot($model);
    }

    public function export(User $user): bool
    {
        return $user->can('users.export');
    }

    public function impersonate(User $user, User $model): bool
    {
        return $user->canImpersonate()
            && $user->isNot($model)
            && $model->canBeImpersonated();
    }
}
