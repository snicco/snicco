<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Event;

use Snicco\Component\BetterWPMail\WPMailAPI;

use function get_class;

final class MailEventsUsingWPHooks implements MailEvents
{
    private WPMailAPI $wp;

    public function __construct(WPMailAPI $wp = null)
    {
        $this->wp = $wp ?: new WPMailAPI();
    }

    public function fireSending(SendingEmail $sending_email): void
    {
        $this->wp->doAction(get_class($sending_email->email), $sending_email);
    }

    public function fireSent(EmailWasSent $email_was_sent): void
    {
        $this->wp->doAction(EmailWasSent::class, $email_was_sent);
    }
}
