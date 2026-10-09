<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Shop\Actions\TransitionOrder;
use Modules\Shop\Domain\Enums\OrderStatus;
use Modules\Shop\Domain\Events\OrderStatusChanged;
use Modules\Shop\Infrastructure\Models\Order;
use Modules\Shop\Listeners\NotifyCustomerOfOrderStatus;
use Modules\Shop\Notifications\OrderStatusChangedNotification;

it('tells the customer when their order moves on', function (): void {
    $customer = User::factory()->create(['email' => 'ada@example.com']);
    $order = Order::factory()->create(['customer_email' => 'ada@example.com']);

    resolve(TransitionOrder::class)->handle($order, OrderStatus::Paid);

    $stored = DatabaseNotification::query()->sole();

    expect($stored->notifiable_id)->toBe($customer->id)
        ->and($stored->type)->toBe(OrderStatusChangedNotification::class)
        ->and($stored->data)->toBe([
            'title' => sprintf('Order %s is now paid', $order->number),
            'body' => 'We received your payment.',
            'url' => route('dashboard'),
            'action' => 'View your orders',
        ]);
});

it('says what each status means', function (OrderStatus $status, ?string $body): void {
    $customer = User::factory()->create();

    $message = new OrderStatusChangedNotification('ORD-1', $status)->message($customer);

    expect($message->title)->toBe(sprintf('Order ORD-1 is now %s', $status->value))
        ->and($message->body)->toBe($body);
})->with([
    'pending' => [OrderStatus::Pending, null],
    'paid' => [OrderStatus::Paid, 'We received your payment.'],
    'shipped' => [OrderStatus::Shipped, 'Your order is on its way.'],
    'cancelled' => [OrderStatus::Cancelled, 'Your order was cancelled. Contact us if this is unexpected.'],
]);

it('notifies nobody when the customer has no verified account', function (): void {
    Notification::fake();
    User::factory()->unverified()->create(['email' => 'ada@example.com']);
    $order = Order::factory()->create(['customer_email' => 'ada@example.com']);

    resolve(TransitionOrder::class)->handle($order, OrderStatus::Paid);

    Notification::assertNothingSent();
});

it('ignores an order that no longer exists', function (): void {
    Notification::fake();
    User::factory()->create(['email' => 'ada@example.com']);

    new NotifyCustomerOfOrderStatus()->handle(new OrderStatusChanged((string) Str::uuid(), 'pending', 'paid'));

    Notification::assertNothingSent();
});

it('writes to the customer in their language', function (): void {
    User::factory()->create(['email' => 'ada@example.com', 'locale' => 'fr']);
    $order = Order::factory()->paid()->create(['customer_email' => 'ada@example.com']);

    resolve(TransitionOrder::class)->handle($order, OrderStatus::Shipped);

    expect(DatabaseNotification::query()->sole()->data)->toMatchArray([
        'title' => sprintf('La commande %s est maintenant expédiée', $order->number),
        'body' => 'Votre commande est en route.',
        'action' => 'Voir vos commandes',
    ])->and(app()->getLocale())->toBe('en');
});
