<?php

namespace Workbench\App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification that the insight demo sends, so the panel has one to list.
 */
class OrderReceiptNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)->subject('Order receipt')->line('Thank you for your order.');
    }
}
