<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Modules\NotificationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base of every in-app notification. A module extends it and describes the
 * message once; it is stored for the bell and the notifications page, and
 * emailed unless the user turned email off. Laravel switches to the user's
 * language (User::preferredLocale) before building it, so translated strings
 * come out in their language. Sent through the queue.
 */
abstract class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function message(User $notifiable): NotificationMessage;

    /**
     * @return list<string>
     */
    final public function via(User $notifiable): array
    {
        return $notifiable->notify_by_email ? ['database', 'mail'] : ['database'];
    }

    /**
     * @return array{title: string, body: string|null, url: string|null, action: string|null}
     */
    final public function toArray(User $notifiable): array
    {
        $message = $this->message($notifiable);

        return [
            'title' => $message->title,
            'body' => $message->body,
            'url' => $message->url,
            'action' => $message->action,
        ];
    }

    final public function toMail(User $notifiable): MailMessage
    {
        $message = $this->message($notifiable);

        $mail = new MailMessage()->subject($message->title)->greeting($message->title);

        if ($message->body !== null) {
            $mail->line($message->body);
        }

        if ($message->url !== null) {
            $mail->action($message->action ?? __('Open'), $message->url);
        }

        return $mail;
    }
}
