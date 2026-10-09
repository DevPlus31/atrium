<?php

declare(strict_types=1);

it('reports up when the database and the cache answer', function (): void {
    $this->getJson('/up')
        ->assertOk()
        ->assertExactJson(['status' => 'up']);
});

it('reports down when the database is unreachable', function (): void {
    $default = config()->string('database.default');
    config()->set('app.debug', false);
    config()->set('database.connections.unreachable', [
        'driver' => 'sqlite',
        'database' => '/nonexistent/atrium.sqlite',
    ]);
    config()->set('database.default', 'unreachable');

    $this->getJson('/up')
        ->assertInternalServerError()
        ->assertExactJson(['status' => 'down']);

    config()->set('database.default', $default);
});
