<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Repositories;

use App\Models\User;
use Modules\Users\Domain\Repositories\UserRepository;

final readonly class EloquentUserRepository implements UserRepository
{
    public function save(User $user): void
    {
        $user->save();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
