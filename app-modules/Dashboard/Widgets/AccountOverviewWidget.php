<?php

declare(strict_types=1);

namespace Modules\Dashboard\Widgets;

use App\Models\User;
use Modules\Dashboard\Data\AccountOverviewWidgetData;

final readonly class AccountOverviewWidget
{
    public function __invoke(User $user): AccountOverviewWidgetData
    {
        return new AccountOverviewWidgetData(
            email_verified: $user->hasVerifiedEmail(),
            two_factor_enabled: $user->hasEnabledTwoFactorAuthentication(),
            passkeys: $user->passkeys()->count(),
            member_since: $user->created_at->toIso8601String(),
        );
    }
}
