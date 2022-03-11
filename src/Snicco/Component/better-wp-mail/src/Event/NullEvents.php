<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Event;

final class NullEvents implements MailEvents
{
    public function fireSending(SendingEmail $sending_email): void
    {
        // Do nothing
    }

    public function fireSent(EmailWasSent $email_was_sent): void
    {
        // Do nothing
    }
}
