<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('_test/ip', fn (): string => (string) request()->ip());
});

it('ignores forwarded client addresses unless the proxy is trusted', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->withHeader('X-Forwarded-For', '203.0.113.9')
        ->get('_test/ip')
        ->assertSee('10.0.0.1');
});

it('uses the forwarded client address from a configured proxy', function (): void {
    config()->set('trustedproxy.proxies', '10.0.0.0/8');

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->withHeader('X-Forwarded-For', '203.0.113.9')
        ->get('_test/ip')
        ->assertSee('203.0.113.9');
});

it('trusts only private-network proxies with the production default', function (string $proxy, string $seenIp): void {
    config()->set('trustedproxy.proxies', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16');

    $this->withServerVariables(['REMOTE_ADDR' => $proxy])
        ->withHeader('X-Forwarded-For', '203.0.113.9')
        ->get('_test/ip')
        ->assertSee($seenIp);
})->with([
    'docker bridge' => ['172.18.0.1', '203.0.113.9'],
    'public address' => ['8.8.8.8', '8.8.8.8'],
]);
