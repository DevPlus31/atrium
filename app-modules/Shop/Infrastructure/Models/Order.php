<?php

declare(strict_types=1);

namespace Modules\Shop\Infrastructure\Models;

use App\Domain\Concerns\InteractsWithDomainEvents;
use App\Domain\Contracts\RecordsDomainEvents;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Shop\Database\Factories\OrderFactory;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\Events\OrderPlaced;
use Modules\Shop\Domain\Events\OrderStatusChanged;
use Modules\Shop\Domain\Exceptions\InvalidCustomerEmail;
use Modules\Shop\Domain\Exceptions\InvalidOrderTransition;
use Modules\Shop\Domain\Exceptions\OrderNotDeletable;
use Modules\Shop\Domain\Exceptions\OrderNotEditable;
use Modules\Shop\Domain\ValueObjects\OrderNumber;

/**
 * @property-read string $id
 * @property string $number
 * @property string $customer_email
 * @property OrderStatus $status
 * @property int $total_cents
 * @property string $currency
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $shipped_at
 * @property CarbonInterface|null $cancelled_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Order extends Model implements RecordsDomainEvents
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    use HasUuids;
    use InteractsWithDomainEvents;

    /**
     * Place a new pending order, recording the event for dispatch after commit.
     */
    public static function place(OrderNumber $number, string $customerEmail, Money $total): self
    {
        $order = new self();
        $order->setAttribute($order->getKeyName(), $order->newUniqueId());
        $order->number = (string) $number;
        $order->customer_email = self::normalizeEmail($customerEmail);
        $order->status = OrderStatus::Pending;
        $order->total_cents = $total->amount;
        $order->currency = $total->currency;

        $order->recordThat(new OrderPlaced($order->id, $order->number, $total->amount, $total->currency));

        return $order;
    }

    /**
     * Move the order along its workflow, stamping when it happened.
     */
    public function transitionTo(OrderStatus $status): void
    {
        $from = $this->status;

        if (! $from->canTransitionTo($status)) {
            throw InvalidOrderTransition::between($this->number, $from, $status);
        }

        $this->status = $status;

        $column = $status->timestampColumn();

        if ($column !== null) {
            $this->setAttribute($column, now());
        }

        $this->recordThat(new OrderStatusChanged($this->id, $from->value, $status->value));
    }

    /**
     * Customer and total may change only before payment.
     */
    public function isEditable(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    /**
     * Fail before deleting an order that must be kept.
     */
    public function ensureDeletable(): void
    {
        if (! $this->isDeletable()) {
            throw OrderNotDeletable::withNumber($this->number);
        }
    }

    /**
     * Paid and shipped orders are kept for the books; only pending or
     * cancelled ones may be deleted.
     */
    public function isDeletable(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Cancelled], true);
    }

    /**
     * Change the customer and total of the order; only before payment.
     */
    public function revise(string $customerEmail, Money $total): void
    {
        if (! $this->isEditable()) {
            throw OrderNotEditable::withNumber($this->number);
        }

        $this->customer_email = self::normalizeEmail($customerEmail);
        $this->total_cents = $total->amount;
        $this->currency = $total->currency;
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'number' => 'string',
            'customer_email' => 'string',
            'status' => OrderStatus::class,
            'total_cents' => 'integer',
            'currency' => 'string',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    /**
     * Customer emails are stored lowercased and trimmed whatever the entry
     * point (form, job, console), so matching a member's email is exact, and
     * an invalid address is refused here rather than only by the form.
     */
    private static function normalizeEmail(string $email): string
    {
        $normalized = mb_strtolower(mb_trim($email));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidCustomerEmail::forValue($email);
        }

        return $normalized;
    }
}
