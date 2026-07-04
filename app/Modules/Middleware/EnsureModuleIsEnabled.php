<?php

declare(strict_types=1);

namespace App\Modules\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureModuleIsEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(Feature::for($request->user())->active('module:'.$module), 404);

        return $next($request);
    }
}
