<?php

declare(strict_types=1);

namespace App\Domain\Concerns;

use App\Domain\Contracts\DomainEvent;

/**
 * Default implementation of {@see \App\Domain\Contracts\RecordsDomainEvents}.
 *
 * Aggregates record events as their invariants change; the owning Action calls
 * {@see self::flushDomainEvents()} once its transaction has committed, so events
 * never fire for work that later rolls back.
 */
trait InteractsWithDomainEvents
{
    /**
     * @var list<DomainEvent>
     */
    private array $domainEvents = [];

    public function recordThat(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     */
    public function releaseDomainEvents(): array
    {
        $events = $this->domainEvents;

        $this->domainEvents = [];

        return $events;
    }

    public function flushDomainEvents(): void
    {
        foreach ($this->releaseDomainEvents() as $event) {
            event($event);
        }
    }
}
