<?php

declare(strict_types=1);

namespace Modules\Users\Domain\Repositories;

use App\Domain\Contracts\Repository;
use App\Models\User;

interface UserRepository extends Repository
{
    public function save(User $user): void;
}
