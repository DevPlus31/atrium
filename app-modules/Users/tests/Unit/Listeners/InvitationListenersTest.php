<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Users\Domain\Events\InvitationAccepted;
use Modules\Users\Domain\Events\InvitationSent;
use Modules\Users\Infrastructure\Models\Invitation;
use Modules\Users\Listeners\NotifyInviterOfAcceptance;
use Modules\Users\Listeners\SendInvitationEmail;
use Modules\Users\Notifications\InvitationAcceptedNotification;
use Modules\Users\Notifications\InvitationNotification;

it('emails the invitation', function (): void {
    Notification::fake();
    $invitation = Invitation::factory()->create();

    new SendInvitationEmail()->handle(new InvitationSent($invitation->id));

    Notification::assertSentOnDemandTimes(InvitationNotification::class, 1);
});

it('sends nothing for an invitation revoked in the meantime', function (): void {
    Notification::fake();

    new SendInvitationEmail()->handle(new InvitationSent((string) Str::uuid()));

    Notification::assertNothingSent();
});

it('tells the inviter, unless there is none or they are gone', function (): void {
    Notification::fake();
    $inviter = User::factory()->create();
    $listener = new NotifyInviterOfAcceptance();

    $listener->handle(new InvitationAccepted('i-1', $inviter->id, 'u-1', 'Grace'));
    $listener->handle(new InvitationAccepted('i-2', null, 'u-2', 'Ada'));
    $listener->handle(new InvitationAccepted('i-3', (string) Str::uuid(), 'u-3', 'Linus'));

    Notification::assertSentToTimes($inviter, InvitationAcceptedNotification::class, 1);
    Notification::assertCount(1);
});
