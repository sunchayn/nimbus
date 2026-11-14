<?php

namespace Workbench\App\Mail;

use Illuminate\Mail\Mailable;

/**
 * A mailable that the insight demo sends, so the panel has a message to list.
 */
class OrderReceiptMail extends Mailable
{
    public function build(): static
    {
        return $this->subject('Your order receipt')->html('<p>Thank you for your order.</p>');
    }
}
