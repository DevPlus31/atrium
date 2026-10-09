<?php

declare(strict_types=1);

namespace Modules\Shop\Domain\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    /**
     * The statuses an order in this status may move to: pending orders get
     * paid or cancelled, paid orders ship or are cancelled (refunded), and
     * shipped or cancelled orders are final.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Shipped, self::Cancelled],
            self::Shipped, self::Cancelled => [],
        };
    }

    /**
     * The timestamp column recording when an order entered this status;
     * pending is the initial status and records nothing extra.
     */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Pending => null,
            self::Paid => 'paid_at',
            self::Shipped => 'shipped_at',
            self::Cancelled => 'cancelled_at',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->transitions(), true);
    }
}
