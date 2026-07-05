<?php

declare(strict_types=1);

namespace Modules\Audit\Policies;

use App\Models\User;

final readonly class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }
}
