<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ResolveUserPreferences;
use App\Enums\ThemePreset;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final readonly class HandleAppearance
{
    public function __construct(private ResolveUserPreferences $preferences)
    {
        //
    }

    /**
     * Share the resolved appearance, theme preset, and direction with the
     * root Blade view so <html> is stamped before first paint (no flash of
     * wrong theme, preset, or direction).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resolved = $this->preferences->handle($request);

        View::share('appearance', $resolved['appearance']->value);
        View::share('theme', $resolved['theme'] === ThemePreset::Default ? null : $resolved['theme']->value);
        View::share('direction', $resolved['layout']->direction->value);

        return $next($request);
    }
}
