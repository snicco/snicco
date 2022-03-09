<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Event;

interface MailEvents
{
    public function fireSending(SendingEmail $sending_email): void;

    public function fireSent(EmailWasSent $email_was_sent): void;
}
