<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final readonly class SecurityHeaders
{
    private const string FONT_ORIGIN = 'https://fonts.bunny.net';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $withPolicy = ! $this->servesBundledToolUi($request);

        if ($withPolicy) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        $response->headers->add([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ]);

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($withPolicy) {
            $response->headers->set('Content-Security-Policy', $this->policy($request));
        }

        return $response;
    }

    /**
     * Scripts must carry the per-request nonce (Vite tags, the theme
     * bootstrap); styles keep 'unsafe-inline' because Radix and Recharts set
     * style attributes, which a nonce cannot cover.
     */
    private function policy(Request $request): string
    {
        $media = $this->mediaOrigin($request);
        $mediaSource = $media === null ? '' : ' '.$media;
        $dev = $this->viteDevServerOrigin();
        $devSources = $dev === null ? '' : ' '.$dev;
        $devSocket = $dev === null ? '' : ' '.preg_replace('/^http/', 'ws', $dev);

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-".Vite::cspNonce()."'".$devSources,
            "style-src 'self' 'unsafe-inline' ".self::FONT_ORIGIN.$devSources,
            "font-src 'self' ".self::FONT_ORIGIN.$devSources,
            "img-src 'self' data:".$mediaSource.$devSources,
            "connect-src 'self'".$devSources.$devSocket,
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }

    /**
     * Where uploaded media is served from when that is another origin (an S3
     * bucket or CDN behind MEDIA_DISK), so photos are not blocked by img-src.
     */
    private function mediaOrigin(Request $request): ?string
    {
        $url = Storage::disk(Config::string('media-library.disk_name'))->url('');
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($scheme) || ! is_string($host)) {
            return null;
        }

        $port = parse_url($url, PHP_URL_PORT);
        $origin = $scheme.'://'.$host.(is_int($port) ? ':'.$port : '');

        return $origin === $request->getSchemeAndHttpHost() ? null : $origin;
    }

    private function viteDevServerOrigin(): ?string
    {
        if (! Vite::isRunningHot()) {
            return null;
        }

        $url = mb_trim((string) file_get_contents(Vite::hotFile()));

        return $url === '' ? null : $url;
    }

    /**
     * Pulse, Horizon and Log Viewer ship their own UIs built on inline
     * scripts, so they get the plain hardening headers without a policy.
     */
    private function servesBundledToolUi(Request $request): bool
    {
        foreach (['pulse.path', 'horizon.path', 'log-viewer.route_path'] as $key) {
            $path = mb_trim(Config::string($key), '/');

            if ($request->is($path, $path.'/*')) {
                return true;
            }
        }

        return false;
    }
}
