<?php

declare(strict_types=1);

namespace Modules\Shop\Notifications;

use App\Models\User;
use App\Modules\NotificationMessage;
use App\Notifications\AppNotification;
use Modules\Shop\Domain\Enums\OrderStatus;

final class OrderStatusChangedNotification extends AppNotification
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly OrderStatus $status,
    ) {
        //
    }

    public function message(User $notifiable): NotificationMessage
    {
        return new NotificationMessage(
            title: __('Order :number is now :status', [
                'number' => $this->orderNumber,
                'status' => __($this->status->value),
            ]),
            body: $this->body(),
            url: route('dashboard'),
            action: __('View your orders'),
        );
    }

    private function body(): ?string
    {
        $body = match ($this->status) {
            OrderStatus::Paid => __('We received your payment.'),
            OrderStatus::Shipped => __('Your order is on its way.'),
            OrderStatus::Cancelled => __('Your order was cancelled. Contact us if this is unexpected.'),
            OrderStatus::Pending => null,
        };

        return is_string($body) ? $body : null;
    }
}
