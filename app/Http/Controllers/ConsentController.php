<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordConsent;
use App\Http\Requests\StoreConsentRequest;
use App\Models\User;
use App\Modules\ConsentCookie;
use App\Modules\Toast;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

/**
 * A visitor's cookie choice, from the consent banner or a preferences form.
 * Open to guests: the choice belongs to the browser.
 */
final readonly class ConsentController
{
    public function store(StoreConsentRequest $request, #[CurrentUser] ?User $user, RecordConsent $action): RedirectResponse
    {
        $cookie = $action(
            visitorId: ConsentCookie::fromRequest($request)?->visitorId,
            user: $user,
            granted: $request->granted(),
            gpc: $request->globalPrivacyControl(),
        );

        Cookie::queue($cookie->toCookie());

        Toast::success(__('Your privacy choices are saved.'));

        return back();
    }
}
