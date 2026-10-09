<?php

declare(strict_types=1);

namespace Modules\Dashboard\Widgets;

use App\Models\User;
use Modules\Dashboard\Data\AccessStatusWidgetData;

final readonly class AccessStatusWidget
{
    /**
     * Shown only to accounts that hold no permission yet, however an
     * administrator might later grant one (role or direct permission).
     */
    public function __invoke(User $user): ?AccessStatusWidgetData
    {
        if ($user->getAllPermissions()->isNotEmpty()) {
            return null;
        }

        return new AccessStatusWidgetData(email: $user->email);
    }
}
