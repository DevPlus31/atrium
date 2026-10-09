<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\Fixtures\Notifications\GreetingNotification;

it('stores the notification and emails it', function (): void {
    $user = User::factory()->create(['name' => 'Ada']);

    expect(new GreetingNotification()->via($user))->toBe(['database', 'mail']);
});

it('only stores it when the user turned email off', function (): void {
    $user = User::factory()->create(['notify_by_email' => false]);

    expect(new GreetingNotification()->via($user))->toBe(['database']);
});

it('stores the message the module described', function (): void {
    $user = User::factory()->create(['name' => 'Ada']);

    expect(new GreetingNotification('https://atrium.test/orders')->toArray($user))->toBe([
        'title' => 'Welcome, Ada',
        'body' => 'Glad you are here.',
        'url' => 'https://atrium.test/orders',
        'action' => 'Take a look',
    ]);
});

it('builds the email from the same message', function (): void {
    $user = User::factory()->create(['name' => 'Ada']);

    $mail = new GreetingNotification('https://atrium.test/orders')->toMail($user);

    expect($mail->subject)->toBe('Welcome, Ada')
        ->and($mail->greeting)->toBe('Welcome, Ada')
        ->and($mail->introLines)->toBe(['Glad you are here.'])
        ->and($mail->actionText)->toBe('Take a look')
        ->and($mail->actionUrl)->toBe('https://atrium.test/orders');
});

it('leaves out the body and button when the message has none', function (): void {
    $user = User::factory()->create(['name' => 'Ada']);

    $mail = new GreetingNotification(body: null)->toMail($user);

    expect($mail->introLines)->toBe([])
        ->and($mail->actionText)->toBeNull();
});

it('queues the notification', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $user->notify(new GreetingNotification());

    Queue::assertPushed(SendQueuedNotifications::class, 2);
});

it('delivers to the bell when the queue runs', function (): void {
    $user = User::factory()->create(['name' => 'Ada']);

    $user->notify(new GreetingNotification());

    $stored = DatabaseNotification::query()->sole();

    expect($stored->notifiable_id)->toBe($user->id)
        ->and($stored->type)->toBe(GreetingNotification::class)
        ->and($stored->data['title'])->toBe('Welcome, Ada')
        ->and($stored->read_at)->toBeNull();
});
