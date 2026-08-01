<?php

declare(strict_types=1);

use App\Domain\Concerns\InteractsWithDomainEvents;
use App\Domain\Contracts\DomainEvent;
use App\Domain\Contracts\RecordsDomainEvents;
use Illuminate\Support\Facades\Event;

it('records and releases domain events, then clears them', function (): void {
    $recorder = new class implements RecordsDomainEvents
    {
        use InteractsWithDomainEvents;
    };

    $event = new class implements DomainEvent {};

    $recorder->recordThat($event);

    expect($recorder->releaseDomainEvents())->toBe([$event])
        ->and($recorder->releaseDomainEvents())->toBe([]);
});

it('dispatches recorded events on flush and clears them', function (): void {
    Event::fake();

    $recorder = new class implements RecordsDomainEvents
    {
        use InteractsWithDomainEvents;
    };

    $event = new class implements DomainEvent {};

    $recorder->recordThat($event);
    $recorder->flushDomainEvents();

    Event::assertDispatched($event::class);

    expect($recorder->releaseDomainEvents())->toBe([]);
});
