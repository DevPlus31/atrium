<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Notifications\InvitationNotification;

it('emails the link, who sent it and when it expires', function (): void {
    $inviter = User::factory()->create(['name' => 'Ada']);
    $invitation = Invitation::factory()->create(['invited_by' => $inviter->id, 'expires_at' => now()->setDate(2026, 10, 15)]);
    $notifiable = new AnonymousNotifiable()->route('mail', $invitation->email);
    $notification = new InvitationNotification($invitation);

    $mail = $notification->toMail($notifiable);

    expect($notification->via($notifiable))->toBe(['mail'])
        ->and($mail->subject)->toBe(sprintf('You are invited to %s', config('app.name')))
        ->and($mail->introLines)->toBe([sprintf('Ada invited you to join %s.', config('app.name'))])
        ->and($mail->actionText)->toBe('Accept invitation')
        ->and($mail->actionUrl)->toBe($invitation->acceptUrl())
        ->and($mail->outroLines)->toBe(['This invitation expires on October 15, 2026.']);
});

it('still reads well when the inviter is gone', function (): void {
    $invitation = Invitation::factory()->create();

    $mail = new InvitationNotification($invitation)->toMail(new AnonymousNotifiable());

    expect($mail->introLines)->toBe([sprintf('You have been invited to join %s.', config('app.name'))]);
});
