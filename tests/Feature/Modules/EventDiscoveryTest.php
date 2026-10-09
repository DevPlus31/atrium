<?php

declare(strict_types=1);

use App\Modules\ListenerClassResolver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Modules\Users\Widgets\RecentUsersWidget;
use Tests\Fixtures\Modules\TestModule\Events\TestModuleEvent;
use Tests\Fixtures\Modules\TestModule\Listeners\RecordTestModuleEvent;

/**
 * @return iterable<int, string>
 */
function eventDiscoveryPaths(): iterable
{
    /** @var iterable<int, string> $paths */
    $paths = (static fn (): iterable => EventServiceProvider::$eventDiscoveryPaths ?? [])
        ->bindTo(null, EventServiceProvider::class)();

    return $paths;
}

it('discovers listeners in the shell and in every module', function (): void {
    $paths = collect(eventDiscoveryPaths())->map(fn (string $path): string => str_replace('/bootstrap/../', '/', $path));

    expect($paths)->toContain(base_path('app/Listeners'), base_path('app-modules/*/Listeners'));
});

it('registers a discovered module listener under its real class name for the event it handles', function (): void {
    $configured = eventDiscoveryPaths();
    EventServiceProvider::setEventDiscoveryPaths([base_path('tests/Fixtures/Modules/*/Listeners')]);

    try {
        $discovered = new EventServiceProvider($this->app)->discoverEvents();
    } finally {
        EventServiceProvider::setEventDiscoveryPaths($configured);
    }

    expect($discovered)->toBe([TestModuleEvent::class => [RecordTestModuleEvent::class.'@handle']]);
});

it('maps files that are not autoloadable classes to a class that handles no event', function (): void {
    expect(ListenerClassResolver::classFromFile(new SplFileInfo(base_path('artisan'))))->toBe(ListenerClassResolver::class);
});

it('maps module files to their Modules namespace', function (): void {
    expect(ListenerClassResolver::classFromFile(new SplFileInfo(base_path('app-modules/Users/Widgets/RecentUsersWidget.php'))))
        ->toBe(RecentUsersWidget::class);
});
