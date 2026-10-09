<?php

declare(strict_types=1);

namespace App\Modules\Middleware;

use App\Modules\ModuleSwitch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureModuleIsEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(ModuleSwitch::isOn($module, $request->user()), 404);

        return $next($request);
    }
}
