<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureTermsAccepted
{
    /**
     * What a user who has not accepted the current terms may still do:
     * accept them, read the legal pages, make cookie choices, exercise their
     * privacy rights (download their data, delete the account) and leave.
     *
     * @var list<string>
     */
    private const array OPEN_ROUTES = [
        'terms-acceptance.*',
        'legal.show',
        'consent.store',
        'privacy.*',
        'user.destroy',
        'password.confirm*',
        'verification.*',
        'impersonation.leave',
        'logout',
    ];

    public function __construct(private ImpersonateManager $impersonation)
    {
        //
    }

    /**
     * Send a signed-in user who has not accepted the current Terms of
     * Service to the acceptance page, then back where they were going.
     * Only page visits are stopped; an admin acting as the user never
     * accepts for them.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User
            || $user->hasAcceptedCurrentTerms()
            || ! $request->isMethod('GET')
            || $request->expectsJson()
            || $request->routeIs(...self::OPEN_ROUTES)
            || $this->impersonation->isImpersonating()) {
            return $next($request);
        }

        return redirect()->guest(route('terms-acceptance.create'));
    }
}
