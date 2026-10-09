<?php

declare(strict_types=1);

namespace Modules\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Modules\Users\Infrastructure\Models\Invitation;

/**
 * The invitation email. The invitee has no account yet, so it goes to the
 * address on demand (Notification::route) and only by mail.
 */
final class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invitation $invitation)
    {
        //
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $app = Config::string('app.name');
        $inviter = $this->invitation->inviter?->name;

        return new MailMessage()
            ->subject(__('You are invited to :app', ['app' => $app]))
            ->line($inviter === null
                ? __('You have been invited to join :app.', ['app' => $app])
                : __(':name invited you to join :app.', ['name' => $inviter, 'app' => $app]))
            ->action(__('Accept invitation'), $this->invitation->acceptUrl())
            ->line(__('This invitation expires on :date.', [
                'date' => $this->invitation->expires_at->locale((string) app()->getLocale())->isoFormat('LL'),
            ]));
    }
}
