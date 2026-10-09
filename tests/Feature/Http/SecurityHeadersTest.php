<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;

beforeEach(function (): void {
    $this->artisan('admin:sync-permissions')->assertSuccessful();
});

it('hardens every web response', function (string $uri): void {
    $response = $this->actingAs(adminUser())->get($uri);

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeaderMissing('Strict-Transport-Security');
})->with([
    'app page' => '/settings/profile',
    'pulse' => '/pulse',
    'horizon' => '/horizon',
    'log viewer' => '/log-viewer',
]);

it('sends a nonce-based content security policy for app pages', function (): void {
    $this->withoutVite();

    $response = $this->get(route('login'));

    $policy = (string) $response->headers->get('Content-Security-Policy');

    preg_match("/script-src 'self' 'nonce-([^']+)'/", $policy, $matches);

    expect($matches)->toHaveCount(2)
        ->and($policy)->toContain("default-src 'self'")
        ->toContain("style-src 'self' 'unsafe-inline' https://fonts.bunny.net")
        ->toContain("font-src 'self' https://fonts.bunny.net")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'")
        ->and($response->getContent())->toContain('<script nonce="'.$matches[1].'">');
});

it('leaves the bundled system tool UIs without a content security policy', function (string $uri): void {
    $this->actingAs(adminUser())->get($uri)->assertHeaderMissing('Content-Security-Policy');
})->with(['/pulse', '/horizon', '/log-viewer']);

it('allows the vite dev server while it is running', function (): void {
    $hot = tempnam(sys_get_temp_dir(), 'hot');
    file_put_contents($hot, 'http://localhost:5173');
    Vite::useHotFile($hot);

    $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

    expect($policy)->toContain('http://localhost:5173')
        ->toContain('ws://localhost:5173');

    unlink($hot);
});

it('enforces https in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

    $this->get('http://localhost/login')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('nonces every executable script the page renders', function (): void {
    $response = $this->get(route('login'));

    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $nonce);
    preg_match_all('/<script\b[^>]*>/i', (string) $response->getContent(), $tags);

    $executable = array_filter($tags[0], static fn (string $tag): bool => ! str_contains($tag, 'application/json'));

    expect($executable)->not->toBeEmpty()
        ->each(fn ($tag) => $tag->toContain('nonce="'.$nonce[1].'"'));
});

it('keeps the vite dev server out of the policy while it is not running', function (): void {
    Vite::useHotFile(storage_path('framework/testing/no-vite-hot-file'));

    $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

    expect($policy)->toMatch("/script-src 'self' 'nonce-[^']+';/")
        ->not->toContain('ws://');
});

it('allows images from a media disk served on another origin', function (): void {
    config()->set('filesystems.disks.media-cdn', ['driver' => 'local', 'root' => storage_path('app/media-cdn'), 'url' => 'https://cdn.example.com/media']);
    config()->set('media-library.disk_name', 'media-cdn');

    $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

    expect($policy)->toContain("img-src 'self' data: https://cdn.example.com");
});

it('adds nothing to img-src when media is served by the app itself', function (): void {
    config()->set('filesystems.disks.media-local', ['driver' => 'local', 'root' => storage_path('app/media-local'), 'url' => 'http://localhost/storage']);
    config()->set('media-library.disk_name', 'media-local');

    $policy = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');

    expect($policy)->toMatch("/img-src 'self' data:;/");
});
