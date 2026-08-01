<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

/**
 * Capability contract for aggregates that accumulate domain events during a
 * unit of work and release them for dispatch once the work has committed.
 */
interface RecordsDomainEvents
{
    public function recordThat(DomainEvent $event): void;

    /**
     * Pull and clear the pending domain events.
     *
     * @return list<DomainEvent>
     */
    public function releaseDomainEvents(): array;

    public function flushDomainEvents(): void;
}
